const calendarFeed = document.querySelector('[data-calendar-feed]');
const calendarFeedStatus = document.querySelector('[data-calendar-feed-status]');
const calendarFeedStatusTop = document.querySelector('[data-calendar-feed-status-top]');
const calendarHeader = document.querySelector('.calendar-header');
const calendarDatePicker = document.querySelector('[data-calendar-date-picker]');
const previousSentinel = document.querySelector('[data-calendar-sentinel="previous"]');
const nextSentinel = document.querySelector('[data-calendar-sentinel="next"]');

const loadedMonths = new Set();
let oldestLoadedMonth = null;
let newestLoadedMonth = null;
let earliestMonth = null;
let currentMonth = null;
let initialMonth = null;
let loadingPrevious = false;
let loadingNext = false;
let allowNextMonthLoad = false;
let initialSetupComplete = false;
let lastScrollY = window.scrollY;

function compareMonthKeys(left, right) {
    return left.localeCompare(right);
}

function shiftMonthKey(monthKey, delta) {
    const [year, month] = monthKey.split('-').map(Number);
    const date = new Date(year, month - 1 + delta, 1);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

function getLoadedMonthKeys() {
    return Array.from(document.querySelectorAll('[data-calendar-month]')).map(
        (section) => section.dataset.calendarMonth
    );
}

function registerLoadedMonth(monthKey) {
    loadedMonths.add(monthKey);

    if (!oldestLoadedMonth || compareMonthKeys(monthKey, oldestLoadedMonth) < 0) {
        oldestLoadedMonth = monthKey;
    }

    if (!newestLoadedMonth || compareMonthKeys(monthKey, newestLoadedMonth) > 0) {
        newestLoadedMonth = monthKey;
    }
}

function setFeedStatus(message, isError = false) {
    if (!calendarFeedStatus) {
        return;
    }

    calendarFeedStatus.textContent = message;
    calendarFeedStatus.classList.toggle('calendar-feed-status--error', isError);
}

function setTopFeedStatus(message, isError = false) {
    if (!calendarFeedStatusTop) {
        return;
    }

    calendarFeedStatusTop.hidden = message === '';
    calendarFeedStatusTop.textContent = message;
    calendarFeedStatusTop.classList.toggle('calendar-feed-status--error', isError);
}

function monthIsLoaded(monthKey) {
    return loadedMonths.has(monthKey);
}

function getFirstMonthSection() {
    if (!calendarFeed) {
        return null;
    }

    return calendarFeed.querySelector('[data-calendar-month]');
}

function insertMonthHtml(monthKey, html) {
    if (!html) {
        return false;
    }

    const firstSection = getFirstMonthSection();
    const isPrepend = Boolean(
        firstSection && compareMonthKeys(monthKey, firstSection.dataset.calendarMonth) > 0
    );

    if (isPrepend) {
        const scrollY = window.scrollY;
        const heightBefore = document.documentElement.scrollHeight;

        firstSection.insertAdjacentHTML('beforebegin', html);

        const heightAfter = document.documentElement.scrollHeight;
        window.scrollTo(0, scrollY + (heightAfter - heightBefore));
    } else {
        previousSentinel.insertAdjacentHTML('beforebegin', html);
    }

    return isPrepend;
}

function canLoadPrevious() {
    if (!oldestLoadedMonth || !earliestMonth) {
        return false;
    }

    return compareMonthKeys(oldestLoadedMonth, earliestMonth) > 0;
}

function canLoadNext() {
    if (!newestLoadedMonth || !currentMonth) {
        return false;
    }

    return compareMonthKeys(newestLoadedMonth, currentMonth) < 0;
}

function updateSentinelState() {
    if (previousSentinel) {
        previousSentinel.hidden = !canLoadPrevious();
    }

    if (nextSentinel) {
        nextSentinel.hidden = !canLoadNext();
    }
}

function enableUpwardScrollIfPastMonth() {
    if (
        initialMonth
        && currentMonth
        && compareMonthKeys(initialMonth, currentMonth) < 0
    ) {
        allowNextMonthLoad = true;
    }
}

function finishInitialSetup() {
    initialSetupComplete = true;
    enableUpwardScrollIfPastMonth();
    updateSentinelState();
}

async function fetchMonth(monthKey) {
    const response = await fetch(`calendar_month.php?month=${encodeURIComponent(monthKey)}`, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error(`Could not load ${monthKey}.`);
    }

    const data = await response.json();

    if (!data || !data.success) {
        throw new Error((data && data.message) || `Could not load ${monthKey}.`);
    }

    return data;
}

async function loadMonthBeforeOldest() {
    if (!canLoadPrevious() || loadingPrevious) {
        return;
    }

    loadingPrevious = true;
    setFeedStatus('Loading earlier drawings...');

    try {
        let monthKey = shiftMonthKey(oldestLoadedMonth, -1);

        while (compareMonthKeys(monthKey, earliestMonth) >= 0) {
            if (monthIsLoaded(monthKey)) {
                break;
            }

            const data = await fetchMonth(monthKey);
            registerLoadedMonth(data.month);

            if (data.html) {
                insertMonthHtml(data.month, data.html);
                break;
            }

            monthKey = shiftMonthKey(monthKey, -1);
        }

        setFeedStatus('');

        if (window.refreshCalendarModalTriggers) {
            window.refreshCalendarModalTriggers();
        }
    } catch (error) {
        setFeedStatus(error.message || 'Could not load earlier drawings.', true);
    } finally {
        loadingPrevious = false;
        updateSentinelState();
    }
}

async function loadMonthAfterNewest() {
    if (!canLoadNext() || loadingNext || !allowNextMonthLoad || !initialSetupComplete) {
        return;
    }

    loadingNext = true;
    setTopFeedStatus('Loading later drawings...');

    try {
        let monthKey = shiftMonthKey(newestLoadedMonth, 1);

        while (compareMonthKeys(monthKey, currentMonth) <= 0) {
            if (monthIsLoaded(monthKey)) {
                break;
            }

            const data = await fetchMonth(monthKey);
            registerLoadedMonth(data.month);

            if (data.html) {
                insertMonthHtml(data.month, data.html);
                break;
            }

            monthKey = shiftMonthKey(monthKey, 1);
        }

        setTopFeedStatus('');

        if (window.refreshCalendarModalTriggers) {
            window.refreshCalendarModalTriggers();
        }
    } catch (error) {
        setTopFeedStatus(error.message || 'Could not load later drawings.', true);
    } finally {
        loadingNext = false;
        updateSentinelState();
    }
}

function updateCalendarHeaderOffset() {
    if (!calendarHeader) {
        return;
    }

    document.documentElement.style.setProperty(
        '--calendar-header-offset',
        `${calendarHeader.offsetHeight + 24}px`
    );
}

function scrollToInitialMonth() {
    if (!calendarFeed) {
        return;
    }

    const initialSection = initialMonth
        ? document.querySelector(`[data-calendar-month="${initialMonth}"]`)
        : null;

    if (!initialSection) {
        return;
    }

    const headerOffset = calendarHeader ? calendarHeader.offsetHeight + 16 : 0;

    window.scrollTo({
        top: initialSection.getBoundingClientRect().top + window.scrollY - headerOffset,
        behavior: 'auto',
    });
}

function submitCalendarDatePicker() {
    if (!calendarDatePicker) {
        return;
    }

    const monthInput = calendarDatePicker.querySelector('[name="calendar_month"]');
    const yearInput = calendarDatePicker.querySelector('[name="calendar_year"]');
    const targetInput = calendarDatePicker.querySelector('[name="month"]');

    if (!monthInput || !yearInput || !targetInput) {
        return;
    }

    targetInput.value = `${yearInput.value}-${monthInput.value}`;
    calendarDatePicker.submit();
}

function initializeCalendarDatePicker() {
    if (!calendarDatePicker) {
        return;
    }

    calendarDatePicker.addEventListener('change', (event) => {
        if (!event.target.matches('[name="calendar_month"], [name="calendar_year"]')) {
            return;
        }

        submitCalendarDatePicker();
    });
}

function initializeCalendarInfiniteScroll() {
    if (!calendarFeed || !previousSentinel || !nextSentinel) {
        return;
    }

    initialMonth = calendarFeed.dataset.initialMonth || null;
    oldestLoadedMonth = initialMonth;
    newestLoadedMonth = initialMonth;
    earliestMonth = calendarFeed.dataset.earliestMonth || oldestLoadedMonth;
    currentMonth = calendarFeed.dataset.currentMonth || oldestLoadedMonth;
    lastScrollY = window.scrollY;

    getLoadedMonthKeys().forEach((monthKey) => {
        registerLoadedMonth(monthKey);
    });

    window.addEventListener(
        'scroll',
        () => {
            if (window.scrollY < lastScrollY) {
                allowNextMonthLoad = true;
            }

            lastScrollY = window.scrollY;
        },
        { passive: true }
    );

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                if (entry.target === previousSentinel) {
                    loadMonthBeforeOldest();
                }

                if (entry.target === nextSentinel) {
                    loadMonthAfterNewest();
                }
            });
        },
        {
            root: null,
            rootMargin: '240px 0px 240px 0px',
            threshold: 0,
        }
    );

    observer.observe(previousSentinel);
    observer.observe(nextSentinel);
}

updateCalendarHeaderOffset();
initializeCalendarDatePicker();
initializeCalendarInfiniteScroll();
window.addEventListener('resize', updateCalendarHeaderOffset);
window.addEventListener('load', () => {
    updateCalendarHeaderOffset();
    scrollToInitialMonth();
    lastScrollY = window.scrollY;

    requestAnimationFrame(() => {
        finishInitialSetup();
    });
});
