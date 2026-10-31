import axios from 'axios';
import { mergeDataIntoQueryString } from '@inertiajs/core';

const DEFAULT_CACHE_TTL_MS = 60 * 1000;
const STALE_REVALIDATE_MS = 15 * 1000;

const pageCache = new Map();
const prefetchQueue = [];
let activePrefetch = null;
let queueTimer = null;
let initialized = false;
let currentUserId = null;

export function normalizeCacheKey(inputUrl) {
    if (!inputUrl || typeof window === 'undefined') return null;
    try {
        const url = new URL(inputUrl.toString(), window.location.origin);
        if (url.origin !== window.location.origin) return null;
        const pathname = url.pathname.length > 1 && url.pathname.endsWith('/')
            ? url.pathname.slice(0, -1)
            : url.pathname;
        return {
            key: `${pathname}${url.search}`,
            pathname,
            url,
        };
    } catch {
        return null;
    }
}

function shouldSkipPrefetch(pathname) {
    if (!pathname) return true;
    return (
        pathname.startsWith('/login') ||
        pathname.startsWith('/logout') ||
        pathname.startsWith('/notifications') ||
        pathname.startsWith('/storage') ||
        pathname.startsWith('/public/') ||
        pathname.includes('/export')
    );
}

function clonePage(page) {
    if (!page) return page;
    try {
        return JSON.parse(JSON.stringify(page));
    } catch {
        return { ...page };
    }
}

export function storePageInCache(page, customKey = null) {
    if (!page || !page.component || !page.url) return;

    const incomingUserId = page.props?.auth?.user?.id ?? null;
    if (currentUserId !== null && incomingUserId !== currentUserId) {
        clearPageCache();
    }
    currentUserId = incomingUserId;

    const normalized = normalizeCacheKey(page.url);
    if (!normalized || shouldSkipPrefetch(normalized.pathname)) return;

    const cleanPage = clonePage(page);
    if (cleanPage.props?.flash) {
        cleanPage.props.flash = {
            ...cleanPage.props.flash,
            success: null,
            error: null,
        };
    }

    const entry = {
        page: cleanPage,
        timestamp: Date.now(),
    };

    pageCache.set(normalized.key, entry);
    if (customKey && customKey !== normalized.key) {
        pageCache.set(customKey, entry);
    }
}

export function clearPageCache() {
    if (queueTimer) {
        clearTimeout(queueTimer);
        queueTimer = null;
    }
    if (activePrefetch) {
        activePrefetch.controller.abort();
        activePrefetch = null;
    }
    prefetchQueue.length = 0;
    pageCache.clear();
}

function scheduleNextPrefetch(router, delayMs = 35) {
    if (queueTimer) clearTimeout(queueTimer);
    queueTimer = setTimeout(() => {
        queueTimer = null;
        processPrefetchQueue(router);
    }, delayMs);
}

function processPrefetchQueue(router) {
    if (activePrefetch || router.activeVisit || prefetchQueue.length === 0) {
        return;
    }

    const nextItem = prefetchQueue.shift();
    if (!nextItem) return;

    if (!nextItem.force) {
        const cached = pageCache.get(nextItem.key);
        if (cached?.page && Date.now() - cached.timestamp < nextItem.cacheFor) {
            nextItem.resolve(cached.page);
            scheduleNextPrefetch(router, 5);
            return;
        }
    }

    const controller = new AbortController();
    const requestPromise = axios
        .get(nextItem.url.href, {
            signal: controller.signal,
            headers: {
                Accept: 'text/html, application/xhtml+xml',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Inertia': true,
                ...(router.page?.version ? { 'X-Inertia-Version': router.page.version } : {}),
            },
        })
        .then((response) => {
            if (response?.headers?.['x-inertia'] && response.data?.component) {
                storePageInCache(response.data, nextItem.key);
                nextItem.resolve(response.data);
                return response.data;
            }
            nextItem.resolve(null);
            return null;
        })
        .catch(() => {
            nextItem.resolve(null);
            return null;
        })
        .finally(() => {
            if (activePrefetch?.controller === controller) {
                activePrefetch = null;
            }
            scheduleNextPrefetch(router, 35);
        });

    activePrefetch = {
        key: nextItem.key,
        controller,
        promise: requestPromise,
    };
}

export function enqueuePrefetch(router, href, { priority = false, cacheFor = DEFAULT_CACHE_TTL_MS, force = false } = {}) {
    const normalized = normalizeCacheKey(href);
    if (!normalized || shouldSkipPrefetch(normalized.pathname)) {
        return Promise.resolve(null);
    }

    if (!force) {
        const existing = pageCache.get(normalized.key);
        if (existing?.page && Date.now() - existing.timestamp < cacheFor) {
            return Promise.resolve(existing.page);
        }
    }

    if (activePrefetch?.key === normalized.key) {
        return activePrefetch.promise;
    }

    const existingQueueIndex = prefetchQueue.findIndex((item) => item.key === normalized.key);
    if (existingQueueIndex !== -1) {
        const [queued] = prefetchQueue.splice(existingQueueIndex, 1);
        if (force) queued.force = true;
        if (priority) {
            prefetchQueue.unshift(queued);
        } else {
            prefetchQueue.push(queued);
        }
        processPrefetchQueue(router);
        return queued.promise;
    }

    let resolveFn;
    const promise = new Promise((resolve) => {
        resolveFn = resolve;
    });

    const item = {
        key: normalized.key,
        url: normalized.url,
        cacheFor,
        force,
        resolve: resolveFn,
        promise,
    };

    if (priority) {
        prefetchQueue.unshift(item);
    } else {
        prefetchQueue.push(item);
    }

    processPrefetchQueue(router);
    return promise;
}

export function prefetchRoutes(router, urls = []) {
    const currentKey = typeof window !== 'undefined' ? normalizeCacheKey(window.location.href)?.key : null;
    for (const url of urls) {
        const normalized = normalizeCacheKey(url);
        if (!normalized || normalized.key === currentKey) continue;
        enqueuePrefetch(router, normalized.url.href, { priority: false });
    }
}

function setupHoverLinkPrefetch(router) {
    if (typeof document === 'undefined') return;

    let hoverTimer = null;

    const extractEligibleHref = (event) => {
        const anchor = event.target?.closest?.('a[href]');
        if (!anchor) return null;
        if (anchor.target && anchor.target !== '_self') return null;
        if (anchor.hasAttribute('download')) return null;
        const href = anchor.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return null;
        }
        const normalized = normalizeCacheKey(href);
        if (!normalized || shouldSkipPrefetch(normalized.pathname)) return null;
        if (normalized.key === normalizeCacheKey(window.location.href)?.key) return null;
        return normalized.url.href;
    };

    document.addEventListener(
        'mouseover',
        (event) => {
            const href = extractEligibleHref(event);
            if (!href) return;
            if (hoverTimer) clearTimeout(hoverTimer);
            hoverTimer = setTimeout(() => {
                enqueuePrefetch(router, href, { priority: true });
            }, 35);
        },
        { passive: true },
    );

    document.addEventListener(
        'mousedown',
        (event) => {
            if (event.button !== 0) return;
            const href = extractEligibleHref(event);
            if (!href) return;
            enqueuePrefetch(router, href, { priority: true });
        },
        { passive: true },
    );
}

export function installInertiaPrefetch(router, initialPage = null) {
    if (initialized) return;
    initialized = true;

    if (initialPage) {
        storePageInCache(initialPage);
    }

    router.prefetch = (href, options = {}) => {
        const cacheFor = typeof options.cacheFor === 'number' ? options.cacheFor : DEFAULT_CACHE_TTL_MS;
        return enqueuePrefetch(router, href, { priority: true, cacheFor });
    };

    router.on('navigate', (event) => {
        const page = event?.detail?.page;
        if (page) {
            storePageInCache(page);
        }
        scheduleNextPrefetch(router, 120);
    });

    router.on('finish', () => {
        scheduleNextPrefetch(router, 100);
    });

    const originalVisit = router.visit.bind(router);

    const swapToCachedPage = (cachedPage, normalized, options) => {
        if (typeof options.onBefore === 'function') {
            const shouldContinue = options.onBefore({ url: normalized.url, method: 'get' });
            if (shouldContinue === false) return;
        }

        if (router.activeVisit) {
            router.cancel();
        }

        if (typeof router.saveScrollPositions === 'function') {
            router.saveScrollPositions();
        }

        const pageToSet = clonePage(cachedPage);
        if (router.page?.props?.auth) {
            pageToSet.props.auth = clonePage(router.page.props.auth);
        }
        if (router.page?.props?.unreadNotificationsCount !== undefined) {
            pageToSet.props.unreadNotificationsCount = router.page.props.unreadNotificationsCount;
        }
        if (pageToSet.props?.flash) {
            pageToSet.props.flash = {
                ...pageToSet.props.flash,
                success: null,
                error: null,
            };
        }
        if (normalized.url.hash && !pageToSet.url.includes('#')) {
            pageToSet.url += normalized.url.hash;
        }

        const preserveScroll = typeof options.preserveScroll === 'function'
            ? Boolean(options.preserveScroll(pageToSet))
            : Boolean(options.preserveScroll);

        const preserveState = typeof options.preserveState === 'function'
            ? Boolean(options.preserveState(pageToSet))
            : options.preserveState === 'errors'
                ? Object.keys(pageToSet.props?.errors || {}).length > 0
                : Boolean(options.preserveState);

        const replace = Boolean(options.replace) || normalized.url.href === window.location.href;

        options.onStart?.({ url: normalized.url, method: 'get' });

        Promise.resolve(
            router.setPage(pageToSet, {
                preserveScroll,
                preserveState,
                replace,
            }),
        ).then(() => {
            options.onSuccess?.(router.page);
            options.onFinish?.({ url: normalized.url, method: 'get', completed: true });
        });
    };

    router.visit = (href, options = {}) => {
        const method = (options.method || 'get').toLowerCase();

        if (method !== 'get') {
            clearPageCache();
            return originalVisit(href, options);
        }

        const hasPartialReload =
            (Array.isArray(options.only) && options.only.length > 0) ||
            (Array.isArray(options.except) && options.except.length > 0) ||
            Boolean(options.forceFormData);

        if (hasPartialReload) {
            if (activePrefetch) {
                activePrefetch.controller.abort();
                activePrefetch = null;
            }
            return originalVisit(href, options);
        }

        const [mergedHref] = mergeDataIntoQueryString(
            'get',
            href.toString(),
            options.data || {},
            options.queryStringArrayFormat || 'brackets',
        );

        const normalized = normalizeCacheKey(mergedHref);
        if (!normalized || shouldSkipPrefetch(normalized.pathname)) {
            return originalVisit(href, options);
        }

        const cached = pageCache.get(normalized.key);
        if (cached?.page) {
            swapToCachedPage(cached.page, normalized, options);

            if (Date.now() - cached.timestamp > STALE_REVALIDATE_MS) {
                enqueuePrefetch(router, normalized.url.href, { priority: false, force: true });
            }
            return;
        }

        if (activePrefetch?.key === normalized.key) {
            activePrefetch.promise.then((prefetchedPage) => {
                if (prefetchedPage) {
                    swapToCachedPage(prefetchedPage, normalized, options);
                } else {
                    originalVisit(href, options);
                }
            });
            return;
        }

        if (activePrefetch) {
            activePrefetch.controller.abort();
            activePrefetch = null;
        }

        return originalVisit(href, options);
    };

    setupHoverLinkPrefetch(router);
}

