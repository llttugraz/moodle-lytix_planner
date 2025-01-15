import Ajax from 'core/ajax';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import {loadFragment} from 'core/fragment';
import {get_string as getString} from 'core/str';
import {makeLoggingFunction} from 'lytix_logs/logs';
import Log from 'core/log';
import ModalType from 'lytix_planner/modal_save_delete_cancel-lazy';
import Widget, {getStrings} from 'lytix_helper/widget';

const svgcontainer = document.getElementById('PlannerWidget');
const plannerLeft = document.getElementById('planner_left');
const plannerRight = document.getElementById('planner_right');
const plannerLegend = document.getElementById('planner_legend');
const plannerOptions = document.getElementById('planner_options');
const plannerActions = document.getElementById('planner_actions');
const plannerMenu = document.getElementById('planner_menu');

// Namespace for svgs
const NS = 'http://www.w3.org/2000/svg';

let svgElement = null;
let svgHeight = 350;
let svgWidth = 600;
let mobileSvgHeight = 600;

let log; // Will be the logging function.

// This will contain all translated event type names as property names, and the corresponding language string as value.
const eventStrings = {};

const planner = {
    contextid: -1,
    courseid: -1,
    userid: -1,
    isteacher: -1,
    strings: null,
    locale: 'default',

    isresizing: false,

    paddingleft: 15,
    paddingright: 15,
    padding: 4,
    barHeight: 20,
    days: 0,
    months: 0,
    weeks: 0,
    startDate: null,
    endDate: null,
    yearStartDate: null,
    daysWidth: 0,
    monthWidth: 0,
    weekWidth: 0,
    weekHeight: 0,
    data: null,
    showMonthFlag: false,

    primaryMarkerColour: '#a09e9e',
    indicatorColour: '#888686',
    fontColour: '#fefefe',
    indicatorStrokeColour: '#595656',
    stillDueColour: '#474747',
    milestoneColour: '#df3540',
    completedColour: '#b2c204',
    thresholdColour: '#f9a606',

    view: null,
    inactiveMonths: [],
    activeMonths: [],
    currentMonth: null,
    weeksInMonth: new Map(),
    daysInWeek: new Map(),
    autoScrolling: false,

    includeTypes: null,
    includedTypes: null,

    storedEvents: new Map(),
    storedMilestones: new Map(),

    dateFormatter: null,
    timeFormatter: null,

    init: function(data) {
        if (svgcontainer !== null) {
            // This is the width we have to work with
            svgWidth = svgcontainer.offsetWidth;
        }

        svgcontainer.innerHTML = '';
        plannerLeft.innerHTML = '';
        plannerRight.innerHTML = '';
        plannerLegend.innerHTML = '';
        plannerOptions.innerHTML = '';
        plannerActions.innerHTML = '';

        const mobileArrows = document.getElementsByClassName('planner-mobileArrows');
        if (mobileArrows.length > 0) {
            mobileArrows[0].innerHTML = '';
            mobileArrows[0].parentNode.removeChild(mobileArrows[0]);
        }

        svgElement = document.createElementNS(NS, 'svg');
        svgElement.setAttributeNS(null, 'id', 'planner-svg');

        document.addEventListener('click', (event) => {
            if (!document.getElementById('planner-mergePopup').contains(event.target)) {
                const mergeElements = document.getElementsByClassName('merge');
                let merge = false;
                mergeElements.forEach(function(element) {
                    if (event.target === element) {
                        merge = true;
                    }
                });
                if (!merge) {
                    this.closePopup();
                }
            }
            if (!document.getElementById('planner-check').contains(event.target) && window.innerWidth < 767) {
                const form = document.getElementById('menuform');
                form.style.left = '-150%';
            }
            if (!document.getElementById('planner-infoMobile').contains(event.target) && window.innerWidth < 767) {
                const info = document.getElementById('planner-info');
                info.style.left = '-150%';
            }
        });

        if (planner.isteacher) {
            document.getElementById("planner_description").innerText = planner.strings.description_teacher;
        } else {
            document.getElementById("planner_description").innerText = planner.strings.description_student;
        }


        planner.data = data;
        // Gets milliseconds since Unix Epoch
        planner.startDate = new Date(data.startDate * 1000);

        const date = new Date(data.endDate * 1000);
        date.setHours(23);
        date.setMinutes(59);

        // Need to get the date in seconds
        planner.endDate = date;

        // Get difference in days
        const msPerDay = 1000 * 60 * 60 * 24;
        // Convert to UTC to respect daylight savings
        const utcStart = Date.UTC(planner.startDate.getFullYear(), planner.startDate.getMonth(), planner.startDate.getDate());
        const utcEnd = Date.UTC(planner.endDate.getFullYear(), planner.endDate.getMonth(), planner.endDate.getDate());
        planner.days = Math.floor((utcEnd - utcStart) / msPerDay);
        planner.months = planner.endDate.getMonth() - planner.startDate.getMonth() + 1
            + (12 * (planner.endDate.getFullYear() - planner.startDate.getFullYear()));

        const currentMonthIndex = new Date().getMonth();
        // Figure out week numbers for each month
        const copiedStartDate = new Date(planner.startDate.getTime());
        for (let i = 0; i < planner.months; i++) {
            const genericMonthCount = new Date(copiedStartDate.getFullYear(), (copiedStartDate.getMonth() + 1), 0).getDate();
            const startDate = new Date(copiedStartDate.getFullYear(), copiedStartDate.getMonth(), 1);
            const endDate = new Date(copiedStartDate.getFullYear(), copiedStartDate.getMonth(), genericMonthCount);
            const weekNR = this.getWeek(startDate);
            const endWeekNR = this.getWeek(endDate);
            if (currentMonthIndex === startDate.getMonth()) {
                planner.currentMonth = i;
            }
            planner.weeksInMonth.set((startDate.getMonth().toString() + startDate.getFullYear().toString()), [weekNR, endWeekNR]);
            copiedStartDate.setMonth((copiedStartDate.getMonth() + 1), 1);
        }
        planner.inactiveMonths.length = 0;
        planner.activeMonths.length = 0;

        if (window.innerWidth <= 767) {
            this.initMobile();
        } else {
            this.initDesktop();
        }
    },

    initDesktop() {
        // Don't set width so we can handle the overflow
        svgElement.setAttributeNS(null, 'height', svgHeight);
        svgcontainer.appendChild(svgElement);

        // Create arrows on left and right for scrolling
        let arrows = this.createArrows(plannerLeft, plannerRight, false);

        // Show left and right indicators of missing events
        this.createIndicators(plannerLeft, plannerRight);

        // Need to add initial dropdown elements to right drop menu
        let rightDrop = document.getElementById('rightArrowDropMenu');
        if ((planner.view === '3 ' + planner.strings.months) || planner.view === null) {
            planner.view = '3 ' + planner.strings.months;
            planner.monthWidth = (svgWidth - (planner.padding * (2)) -
                (planner.paddingleft + planner.paddingright)) / 3;
            // Daywith also dependant on view
            planner.dayWidth = (svgWidth - (planner.padding * (2)) -
                (planner.paddingleft + planner.paddingright)) / planner.days;
            // Show the scrollbar
            svgcontainer.style.overflowX = 'auto';
            // Need to set the width of the svg as it does not recognize it
            let sWidth = planner.monthWidth * planner.months +
                planner.paddingright +
                planner.paddingleft +
                planner.months * planner.padding;
            svgElement.setAttributeNS(null, 'width', sWidth);

            let pos = 0;
            if (planner.months > (planner.currentMonth) && planner.currentMonth - 1 >= 0) {
                // There are months to the left and right of the month
                pos = -1;
            } else if (planner.currentMonth - 1 >= 0) {
                // Means there are only months to the left
                if (planner.currentMonth - 2 >= 0) {
                    pos = -2;
                } else {
                    pos = -1;
                }
            } else {
                pos = 0;
            }

            const scrollToAmount = (planner.monthWidth + planner.padding) * (planner.currentMonth + pos);
            // Move the scroll bar to the correct position
            svgcontainer.scrollTo({
                top: 0,
                left: scrollToAmount,
                behavior: 'instant',
            });

            this.addHiddenMonths();

            // Add initial dropdown items, then change dynamically
            let date = new Date(planner.startDate.getTime());

            planner.activeMonths.push([date.getMonth(), date.getFullYear()]);
            date.setMonth((date.getMonth() + 1));
            if (planner.months > 1) {
                planner.activeMonths.push([date.getMonth(), date.getFullYear()]);
                date.setMonth((date.getMonth() + 1));
                if (planner.months > 2) {
                    planner.activeMonths.push([date.getMonth(), date.getFullYear()]);
                    date.setMonth((date.getMonth() + 1));
                }
            }

            let nextM = document.createElement('a');
            nextM.setAttribute('href', '#');
            nextM.innerText = planner.strings.next + ' >';
            nextM.addEventListener('click', () => {
                // Just scroll to the next month that is not in view
                const newPos = (svgcontainer.scrollLeft / planner.monthWidth + 1).toFixed(0)
                    * (planner.monthWidth + planner.padding);
                svgcontainer.scrollTo({
                    top: 0,
                    left: newPos,
                    behavior: 'smooth',
                });
            });
            arrows[0].addEventListener('click', () => {
                // Just scroll to the next month that is not in view
                const newPos = (svgcontainer.scrollLeft / planner.monthWidth - 1).toFixed(0)
                    * (planner.monthWidth + planner.padding);
                svgcontainer.scrollTo({
                    top: 0,
                    left: newPos,
                    behavior: 'smooth',
                });
            });
            arrows[1].addEventListener('click', () => {
                // Just scroll to the next month that is not in view
                const newPos = (svgcontainer.scrollLeft / planner.monthWidth + 1).toFixed(0)
                    * (planner.monthWidth + planner.padding);
                svgcontainer.scrollTo({
                    top: 0,
                    left: newPos,
                    behavior: 'smooth',
                });
            });

            rightDrop.appendChild(nextM);

        } else if (planner.view === planner.strings.month) {
            planner.monthWidth = svgWidth - (planner.paddingleft + planner.paddingright);
            // Daywidth also has to be changed depending on view
            planner.dayWidth = (svgWidth - (planner.paddingleft + planner.paddingright)) / planner.days;
            svgcontainer.style.overflowX = 'auto';
            const sWidth = (planner.monthWidth + planner.padding) * planner.months +
                planner.paddingright +
                planner.paddingleft;
            svgElement.setAttributeNS(null, 'width', sWidth);
            // We should scrollTo the screen so that the current date is in the middle
            const scrollToAmount = (planner.monthWidth + planner.padding) * (planner.currentMonth);
            // Move the scroll bar to the correct position
            svgcontainer.scrollTo({
                top: 0,
                left: scrollToAmount,
                behavior: 'instant',
            });
            this.addHiddenMonths();

            // Add navigation buttons
            const date = new Date(planner.startDate.getTime());
            planner.activeMonths.push([date.getMonth(), date.getFullYear()]);
            date.setMonth((date.getMonth() + 1));
            const nextM = document.createElement('a');
            nextM.setAttribute('href', '#');
            nextM.innerText = planner.strings.next + ' >';
            nextM.addEventListener('click', () => {
                // Just scroll to the next month that is not in view
                const newPos = (svgcontainer.scrollLeft / planner.monthWidth + 1).toFixed(0)
                    * (planner.monthWidth + planner.padding);
                svgcontainer.scrollTo({
                    top: 0,
                    left: newPos,
                    behavior: 'smooth',
                });
            });
            arrows[1].addEventListener('click', () => {
                // Just scroll to the next month that is not in view
                const newPos = (svgcontainer.scrollLeft / planner.monthWidth + 1).toFixed(0)
                    * (planner.monthWidth + planner.padding);
                svgcontainer.scrollTo({
                    top: 0,
                    left: newPos,
                    behavior: 'smooth',
                });
            });
            arrows[0].addEventListener('click', () => {
                // Just scroll to the next month that is not in view
                const newPos = (svgcontainer.scrollLeft / planner.monthWidth - 1).toFixed(0)
                    * (planner.monthWidth + planner.padding);
                svgcontainer.scrollTo({
                    top: 0,
                    left: newPos,
                    behavior: 'smooth',
                });
            });

        } else {
            let tooSmall = false;
            planner.monthWidth = (svgWidth - (planner.padding * (planner.months - 1)) -
                (planner.paddingleft + planner.paddingright)) / planner.months;
            // Daywidth also has to be changed depending on view
            planner.dayWidth = (svgWidth - (planner.padding * (planner.months - 1)) -
                (planner.paddingleft + planner.paddingright)) / planner.days;
            if (planner.monthWidth < 60) {
                // 60px is the minimum width of a month, otherwise the month text won't be visible.
                tooSmall = true;
                planner.monthWidth = 60;
                planner.dayWidth = (60 * planner.months) / planner.days;
            }

            if (!tooSmall) {
                svgElement.setAttributeNS(null, 'width', svgWidth);
                svgcontainer.style.overflowX = 'hidden';
            } else {
                const sWidth = (planner.monthWidth + planner.padding)
                    * planner.months
                    + planner.paddingright
                    + planner.paddingleft;
                svgElement.setAttributeNS(null, 'width', sWidth);
                svgcontainer.style.overflowX = 'scroll';
            }
            const date = new Date(planner.startDate.getTime());
            for (let i = 0; i < planner.months; i++) {
                planner.activeMonths.push([date.getMonth(), date.getFullYear()]);
                date.setMonth((date.getMonth() + 1));
            }
        }
        svgcontainer.addEventListener('scroll', this.addHiddenMonths);
    },
    initMobile() {
        // Get height to draw svg
        // Don't set width so we can handle the overflow
        svgElement.setAttributeNS(null, 'height', mobileSvgHeight);
        planner.view = planner.strings.month;
        const plannerView = document.getElementById('planner_view');

        // Create place for arrows here
        const arrowPlacing = document.createElement('div');
        plannerView.prepend(arrowPlacing);
        const leftPlace = document.createElement('div');

        const rightPlace = document.createElement('div');
        const middlePlace = document.createElement('div');
        arrowPlacing.appendChild(middlePlace);
        arrowPlacing.appendChild(leftPlace);
        arrowPlacing.appendChild(rightPlace);
        arrowPlacing.setAttribute('class', 'planner-mobileArrows');

        const arrows = this.createArrows(leftPlace, rightPlace, true);
        this.createIndicators(leftPlace, rightPlace);

        svgcontainer.appendChild(svgElement);
        let rightDrop = document.getElementById('rightArrowDropMenu');
        // A week stretches the whole width and we have 6 weeks showing at any point
        planner.weekWidth = svgWidth - (planner.paddingleft + planner.paddingright);
        planner.monthWidth = planner.weekWidth;
        // Daywidth also has to be changed depending on view
        planner.dayWidth = planner.weekWidth / 7;
        // Also on mobile we will have overflow per month
        svgcontainer.style.overflowX = 'auto';

        let sWidth = (planner.weekWidth + planner.padding) * planner.months +
            planner.paddingright +
            planner.paddingleft;
        svgElement.setAttributeNS(null, 'width', sWidth);

        // Add initial dropdown items, then change dynamically
        const date = new Date();
        planner.activeMonths.push([date.getMonth(), date.getFullYear()]);

        // Create the heading with current month
        this.createMobileMonth(middlePlace, date.getMonth(), date.getFullYear());

        const newPos = (planner.monthWidth + planner.padding) * (planner.currentMonth);
        svgcontainer.scrollTo({
            top: 0,
            left: newPos,
            behavior: 'instant',
        });

        const nextM = document.createElement('a');
        nextM.setAttribute('href', '#');
        nextM.innerText = planner.strings.next + ' >';
        nextM.addEventListener('click', () => {
            // Just scroll to the next month that is not in view
            const newPos = (svgcontainer.scrollLeft / planner.monthWidth + 1).toFixed(0) * (planner.monthWidth + planner.padding);
            svgcontainer.scrollTo({
                top: 0,
                left: newPos,
                behavior: 'smooth',
            });
        });
        arrows[1].addEventListener('click', () => {
            // Just scroll to the next month that is not in view
            const newPos = (svgcontainer.scrollLeft / planner.monthWidth + 1).toFixed(0) * (planner.monthWidth + planner.padding);
            svgcontainer.scrollTo({
                top: 0,
                left: newPos,
                behavior: 'smooth',
            });
        });
        arrows[0].addEventListener('click', () => {
            // Just scroll to the next month that is not in view
            const newPos = (svgcontainer.scrollLeft / planner.monthWidth - 1).toFixed(0) * (planner.monthWidth + planner.padding);
            svgcontainer.scrollTo({
                top: 0,
                left: newPos,
                behavior: 'smooth',
            });
        });
        rightDrop.appendChild(nextM);
        this.addHiddenMonths();
        svgcontainer.addEventListener('scroll', this.addHiddenMonths);
    },

    getType: function(a) {
        return a.type;
    },
    createArrows: function(plannerLeft, plannerRight, mobile) {
        // Create arrows for navigation
        const leftArrow = document.createElementNS(NS, 'svg');
        leftArrow.setAttributeNS(null, 'width', 45);
        leftArrow.setAttributeNS(null, 'height', 43);
        leftArrow.setAttributeNS(null, 'fill', 'none');
        leftArrow.setAttributeNS(null, 'id', 'leftArrow');

        const drawing = document.createElementNS(NS, 'path');
        drawing.setAttributeNS(null, 'd', 'M3.54082 22.8031C2.07838 22.0621 2.07979 19.9729 3.54323 19.2339L33.1014 ' +
            '4.30629C34.4322 3.63422 36.004 4.60203 36.003 6.09289L35.9829 35.9877C35.9819 37.4786 34.4088 38.4443 ' +
            '33.0789 37.7704L3.54082 22.8031Z');

        drawing.setAttributeNS(null, 'fill', planner.indicatorColour);
        leftArrow.appendChild(drawing);

        leftArrow.style.position = 'absolute';
        leftArrow.style.bottom = '0px';
        leftArrow.style.left = '0px';

        let dropContainer = document.createElement('div');
        dropContainer.setAttribute('class', 'dropdown');

        const leftButton = document.createElement('button');
        leftButton.setAttribute('width', leftArrow.getAttribute('width'));
        leftButton.setAttribute('height', 43);
        leftButton.setAttribute('type', 'button');

        let dropMenu = document.createElement('div');
        dropMenu.setAttribute('class', 'dropdown-content');
        if (mobile) {
            dropMenu.setAttribute('class', 'dropdown-content mobile');
        }
        dropMenu.setAttribute('id', 'leftArrowDropMenu');

        dropContainer.style.position = 'absolute';
        dropContainer.style.bottom = '0px';
        dropContainer.style.left = '0px';

        leftButton.style.padding = '0px';
        leftButton.style.border = 'none';

        leftButton.appendChild(dropMenu);
        leftButton.appendChild(leftArrow);
        dropContainer.appendChild(leftButton);

        plannerLeft.appendChild(dropContainer);

        const rightArrow = document.createElementNS(NS, 'svg');
        rightArrow.setAttributeNS(null, 'width', 45);
        rightArrow.setAttributeNS(null, 'height', 43);
        rightArrow.setAttributeNS(null, 'fill', 'none');
        rightArrow.setAttributeNS(null, 'id', 'rightArrow');
        const drawingRight = document.createElementNS(NS, 'path');
        drawingRight.setAttributeNS(null, 'd', 'M44.5203 19.2726C45.9851 20.009 45.9903 22.0982 44.5293' +
            ' 22.8419L15.0188 37.8636C13.6902 38.5399 12.1153 37.5771 12.1115 36.0863L12.0364 6.19149C12.0326' +
            ' 4.70064 13.6027 3.72993 14.9347 4.39955L44.5203 19.2726Z');

        drawingRight.setAttributeNS(null, 'fill', planner.indicatorColour);
        rightArrow.appendChild(drawingRight);
        rightArrow.style.position = 'absolute';
        rightArrow.style.bottom = '0px';
        rightArrow.style.right = '0px';

        dropContainer = document.createElement('div');
        dropContainer.setAttribute('class', 'dropdown');

        const rightButton = document.createElement('button');
        rightButton.setAttribute('width', rightArrow.getAttribute('width'));
        rightButton.setAttribute('height', 43);
        rightButton.setAttribute('type', 'button');

        dropMenu = document.createElement('div');
        dropMenu.setAttribute('class', 'dropdown-content');
        if (mobile) {
            dropMenu.setAttribute('class', 'dropdown-content mobile');
        }
        dropMenu.setAttribute('id', 'rightArrowDropMenu');

        dropMenu.style.right = '0px';

        dropContainer.style.position = 'absolute';
        dropContainer.style.bottom = '0px';
        dropContainer.style.right = '0px';
        rightButton.style.padding = '0px';
        rightButton.style.border = 'none';

        rightButton.appendChild(dropMenu);
        rightButton.appendChild(rightArrow);
        dropContainer.appendChild(rightButton);
        plannerRight.appendChild(dropContainer);

        return [leftArrow, rightArrow];
    },
    createIndicators: function(plannerLeft, plannerRight) {
        const leftIndicator = document.createElementNS(NS, 'svg');
        leftIndicator.setAttributeNS(null, 'height', 25);
        leftIndicator.setAttributeNS(null, 'width', 25);
        leftIndicator.style.position = 'absolute';
        leftIndicator.style.left = '0';
        leftIndicator.style.bottom = '40px';
        const rightIndicator = document.createElementNS(NS, 'svg');
        rightIndicator.setAttributeNS(null, 'height', 25);
        rightIndicator.setAttributeNS(null, 'width', 25);
        rightIndicator.style.position = 'absolute';
        rightIndicator.style.right = '0px';
        rightIndicator.style.bottom = '40px';

        // Create a group for text and rect inside the svg
        let leftGroup = document.createElementNS(NS, 'g');
        let rightGroup = document.createElementNS(NS, 'g');
        leftIndicator.appendChild(leftGroup);
        rightIndicator.appendChild(rightGroup);

        let indicator = document.createElementNS(NS, 'rect');
        indicator.setAttributeNS(null, 'x', 1);
        indicator.setAttributeNS(null, 'y', 1);
        indicator.setAttributeNS(null, 'height', 22);
        indicator.setAttributeNS(null, 'width', 22);
        indicator.setAttributeNS(null, 'rx', 4);
        indicator.style.fill = planner.indicatorColour;
        indicator.style.stroke = planner.indicatorStrokeColour;
        indicator.style.strokeWidth = 2;

        // Create textelement for inside
        let leftText = document.createElementNS(NS, 'text');
        // These sizes and positions work for up to two digits only
        leftText.setAttributeNS(null, 'id', 'leftText');
        leftText.setAttributeNS(null, 'x', 12);
        leftText.setAttributeNS(null, 'y', 18);
        leftText.setAttributeNS(null, 'dy', '0px');
        leftText.style.cssText += 'text-anchor:middle;font-weight:bold;font-family:sans-serif';
        leftText.setAttributeNS(null, 'fill', planner.fontColour);
        leftGroup.appendChild(indicator);
        leftGroup.appendChild(leftText);

        indicator = document.createElementNS(NS, 'rect');
        indicator.setAttributeNS(null, 'x', 1);
        indicator.setAttributeNS(null, 'y', 1);
        indicator.setAttributeNS(null, 'height', 22);
        indicator.setAttributeNS(null, 'width', 22);
        indicator.setAttributeNS(null, 'rx', 4);
        indicator.style.fill = planner.indicatorColour;
        indicator.style.stroke = planner.indicatorStrokeColour;
        indicator.style.strokeWidth = 2;

        let rightText = document.createElementNS(NS, 'text');
        // RightText sizes and positions work for up to two digits only
        rightText.setAttributeNS(null, 'id', 'rightText');
        rightText.setAttributeNS(null, 'x', 12);
        rightText.setAttributeNS(null, 'y', 18);
        rightText.setAttributeNS(null, 'dy', '0px');
        rightText.style.cssText += 'text-anchor:middle;font-weight:bold;font-family:sans-serif';
        rightText.setAttributeNS(null, 'fill', planner.fontColour);

        rightGroup.appendChild(indicator);
        rightGroup.appendChild(rightText);

        plannerLeft.appendChild(leftIndicator);
        plannerRight.appendChild(rightIndicator);
    },

    createMobileMonth: function(area, month, year) {
        const monthText = document.createElement('h2');
        const current = new Date(year, month);
        monthText.innerText = current.toLocaleString(planner.locale, {month: 'long', year: 'numeric'});
        monthText.setAttribute('class', 'planner-mobileMonth');
        monthText.setAttribute('id', 'mobileMonth');
        area.appendChild(monthText);
        return monthText;
    },
    addHiddenMonths: function() {
        // Depending on the display type, we may have hidden months on left and right that need to go into the dropdown
        if (planner.view === 'gesamtes Semester' || planner.view === 'entire semester') {
            return;
        }
        const mobile = (window.innerWidth < 767);
        const date = new Date(planner.startDate.getTime());
        const scrollPos = svgcontainer.scrollLeft;
        const rightDropMenu = document.getElementById('rightArrowDropMenu');
        const leftDropMenu = document.getElementById('leftArrowDropMenu');
        // Removing all children to add accurate ones
        const nextM = document.createElement('a');
        nextM.innerText = planner.strings.next + ' >';
        nextM.addEventListener('click', () => {
            // Just scroll to the next month that is not in view
            let newPos = (svgcontainer.scrollLeft / planner.monthWidth + 1).toFixed(0) * (planner.monthWidth + planner.padding);
            svgcontainer.scrollTo({
                top: 0,
                left: newPos,
                behavior: 'smooth',
            });
        });
        rightDropMenu.appendChild(nextM);
        let child = rightDropMenu.lastElementChild;
        while (child !== rightDropMenu.firstElementChild) {
            rightDropMenu.removeChild(child);
            child = rightDropMenu.lastElementChild;
        }
        const nextMLeft = document.createElement('a');
        nextMLeft.setAttribute('href', '#');
        nextMLeft.innerText = '< ' + planner.strings.previous_month;
        nextMLeft.addEventListener('click', () => {
            // Just scroll to the next month that is not in view
            const newPos = (svgcontainer.scrollLeft / planner.monthWidth - 1).toFixed(0) * (planner.monthWidth + planner.padding);
            svgcontainer.scrollTo({
                top: 0,
                left: newPos,
                behavior: 'smooth',
            });
        });
        leftDropMenu.appendChild(nextMLeft);
        child = leftDropMenu.lastElementChild;
        while (child !== leftDropMenu.firstElementChild) {
            leftDropMenu.removeChild(child);
            child = leftDropMenu.lastElementChild;
        }
        // Clear inactive months, will be set fresh now
        planner.inactiveMonths.length = 0;
        planner.activeMonths.length = 0;
        if (planner.view === '3 ' + planner.strings.months) {
            // Show all months where condition applies that scrollpos plus something is still bigger than screen
            for (let i = 0; i < planner.months; i++) {
                // Show in right button dropdown hover
                if (i * planner.monthWidth + planner.monthWidth * 0.25 < scrollPos) {
                    planner.inactiveMonths.push([date.getMonth(), date.getFullYear()]);
                    const month = document.createElement('a');
                    month.setAttribute('class', 'dropdown-item');
                    month.setAttribute('href', '#');
                    month.innerText = date.toLocaleString(planner.locale, {year: 'numeric', month: 'short'});
                    month.addEventListener('click', () => {
                        const scrollToAmount = (planner.monthWidth + planner.padding) * i - planner.monthWidth;
                        // Move the scroll bar to the correct position
                        svgcontainer.scrollTo({
                            top: 0,
                            left: scrollToAmount,
                            behavior: 'smooth',
                        });

                    });

                    leftDropMenu.appendChild(month);
                } else if (i * planner.monthWidth + planner.monthWidth * 0.75 >= scrollPos + planner.monthWidth * 3) {
                    planner.inactiveMonths.push([date.getMonth(), date.getFullYear()]);
                    // Still need to add the padding to make it accurate
                    const month = document.createElement('a');
                    month.setAttribute('class', 'dropdown-item');
                    month.setAttribute('href', '#');
                    month.innerText = date.toLocaleString(planner.locale, {year: 'numeric', month: 'short'});
                    month.addEventListener('click', () => {
                        const scrollToAmount = (planner.monthWidth + planner.padding) * i - planner.monthWidth;
                        // Move the scroll bar to the correct position
                        svgcontainer.scrollTo({
                            top: 0,
                            left: scrollToAmount,
                            behavior: 'smooth',
                        });
                    });
                    rightDropMenu.appendChild(month);
                } else {
                    // Month must be currently active and in view
                    planner.activeMonths.push([date.getMonth(), date.getFullYear()]);
                }
                date.setMonth((date.getMonth() + 1));
            }

        } else {
            for (let i = 0; i < planner.months; i++) {
                if (i * planner.monthWidth + planner.monthWidth * 0.75 < scrollPos) {
                    planner.inactiveMonths.push([date.getMonth(), date.getFullYear()]);
                    const month = document.createElement('a');
                    month.setAttribute('class', 'dropdown-item');
                    month.setAttribute('href', '#');
                    month.innerText = date.toLocaleString(planner.locale, {year: 'numeric', month: 'short'});
                    month.addEventListener('click', () => {
                        const scrollToAmount = (planner.monthWidth + planner.padding) * i;
                        // Move the scroll bar to the correct position
                        svgcontainer.scrollTo({
                            top: 0,
                            left: scrollToAmount,
                            behavior: 'smooth',
                        });
                    });
                    leftDropMenu.appendChild(month);
                } else if (i * planner.monthWidth + planner.monthWidth * 0.75 >= svgcontainer.scrollLeft + planner.monthWidth) {
                    // Show in right button dropdown hover
                    planner.inactiveMonths.push([date.getMonth(), date.getFullYear()]);
                    // Still need to add the padding to make it accurate
                    const month = document.createElement('a');
                    month.setAttribute('class', 'dropdown-item');
                    month.setAttribute('href', '#');
                    month.innerText = date.toLocaleString(planner.locale, {year: 'numeric', month: 'short'});
                    month.addEventListener('click', () => {
                        const scrollToAmount = (planner.monthWidth + planner.padding) * i;
                        // Move the scroll bar to the correct position
                        svgcontainer.scrollTo({
                            top: 0,
                            left: scrollToAmount,
                            behavior: 'smooth',
                        });
                    });

                    rightDropMenu.appendChild(month);

                } else {
                    // Must be active and currently in view
                    planner.activeMonths.push([date.getMonth(), date.getFullYear()]);
                    if (mobile) {
                        // For mobile header, I can now also set the heading accordingly
                        const mobileHeader = document.getElementById('mobileMonth');
                        const currentDate = new Date(date.getFullYear(), date.getMonth());
                        mobileHeader.innerText = currentDate.toLocaleString(planner.locale, {
                            month: 'long',
                            year: 'numeric'
                        });
                    }

                }
                date.setMonth((date.getMonth() + 1));
            }
        }
        // Remove the next button if no actual next month exists on left or right side
        if (leftDropMenu.lastElementChild === leftDropMenu.firstElementChild) {
            leftDropMenu.removeChild(leftDropMenu.firstElementChild);
        }
        if (rightDropMenu.lastElementChild === rightDropMenu.firstElementChild) {
            rightDropMenu.removeChild(rightDropMenu.firstElementChild);
        }

        planner.updateIndicatorAmounts();
    },

    updateIndicatorAmounts: function() {
        // First update quantity in indicators
        // Check how many events in this month and add to indicator
        const rightText = document.getElementById('rightText');
        const leftText = document.getElementById('leftText');
        let leftAmount = 0;
        let rightAmount = 0;

        for (let [key, value] of planner.storedEvents) {
            const date = new Date(key);
            for (let i = 0; i < planner.inactiveMonths.length; i++) {
                const [month, year] = planner.inactiveMonths.at(i);
                if (date.getMonth() === month && date.getFullYear() === year) {
                    if ((month < planner.activeMonths.at(0)[0] && year <= planner.activeMonths.at(0)[1]) ||
                        year < planner.activeMonths.at(0)[1]) {
                        // Means it is inactive on left
                        leftAmount += value;
                    } else {
                        // Means it is inactive on right
                        rightAmount += value;
                    }
                }
            }
        }
        leftText.textContent = leftAmount.toString();
        rightText.textContent = rightAmount.toString();
    },

    storeEvents: function(events) {
        planner.storedEvents = new Map();
        planner.storedMilestones = new Map();
        for (let i = 0; i < events.length; i++) {
            let date = new Date(events[i].startdate * 1000);
            date.setHours(0);
            date.setMinutes(0);
            date = date.getTime();
            if (events[i].type === 'Milestone' || events[i].type === 'Meilenstein') {
                if (planner.storedMilestones.get(date)) {
                    planner.storedMilestones.set(date, planner.storedMilestones.get(date) + 1);
                } else {
                    planner.storedMilestones.set(date, 1);
                }
            } else {
                if (planner.storedEvents.get(date)) {
                    planner.storedEvents.set(date, planner.storedEvents.get(date) + 1);
                } else {
                    planner.storedEvents.set(date, 1);
                }
            }
        }
    },

    drawLoading: function() {
        var imgtype;
        if (Number(M.cfg.version) < 2024042200) {
            imgtype = "gif";
        } else {
            imgtype = "svg";
        }
        const img = '<img src="../../../pix/i/loading.' + imgtype + '" ' +
            'alt="LoadingImage" style="width:48px;height:48px;">';

        svgcontainer.innerHTML = img + ' ' + planner.strings.loading_msg;
    },

    updateSvgHeight: function(height) {
        document.querySelector('#planner-svg').setAttributeNS(null, 'height', height);
    },

    drawplanner: function() {
        const y = planner.barPosY - planner.barHeight / 2;
        const date = new Date(planner.startDate.getTime());
        // First update quantity in indicators
        // Check how many events in this month and add to indicator
        const rightText = document.getElementById('rightText');
        const leftText = document.getElementById('leftText');
        let leftAmount = 0;
        let rightAmount = 0;
        for (let [key, value] of planner.storedEvents) {
            const newdate = new Date(key);
            for (let i = 0; i < planner.inactiveMonths.length; i++) {
                const [month, year] = planner.inactiveMonths.at(i);
                if (newdate.getMonth() === month && newdate.getFullYear() === year) {
                    if (month < planner.activeMonths.at(0)[0] && year <= planner.activeMonths.at(0)[1]) {
                        // Means it is inactive on left
                        leftAmount += value;
                    } else {
                        // Means it is inactive on right
                        rightAmount += value;
                    }
                }
            }
        }

        leftText.textContent = leftAmount.toString();
        rightText.textContent = rightAmount.toString();
        if (window.innerWidth <= 767) {
            this.drawMobile(y, date);
        } else {

            this.drawDesktop(leftText, rightText, leftAmount, rightAmount, y, date);
        }
    },
    drawDesktop: function(leftText, rightText, leftAmount, rightAmount, y, date) {
        for (let i = 0; i < planner.months; ++i) {
            // Inserting line for each month
            const newElementline = document.createElementNS(NS, 'rect');
            const x = planner.paddingleft + i * planner.monthWidth + i * planner.padding;
            const x1 = planner.paddingleft + (i + 1) * planner.monthWidth + i * planner.padding;
            const width = x1 - x;
            const height = planner.barHeight;
            newElementline.setAttributeNS(null, 'x', x);
            newElementline.setAttributeNS(null, 'width', width);
            newElementline.setAttributeNS(null, 'y', y);
            newElementline.setAttributeNS(null, 'height', height);
            newElementline.setAttributeNS(null, 'rx', 5);
            newElementline.setAttributeNS(null, 'cursor', 'pointer');
            newElementline.setAttributeNS(null, 'stroke', '#000');
            svgElement.appendChild(newElementline);

            // Inserting text into line
            const newElementtext = document.createElementNS(NS, 'text');
            const textx = planner.paddingleft + i * planner.padding + i * planner.monthWidth + planner.monthWidth / 2;
            newElementtext.setAttributeNS(null, 'x', textx);
            newElementtext.setAttributeNS(null, 'y', y + height / 2);
            newElementtext.setAttributeNS(null, 'dy', '6px');
            newElementtext.setAttributeNS(null, 'cursor', 'pointer');
            newElementtext.style.textAnchor = 'middle';
            newElementtext.style.fontWeight = 'normal';
            newElementtext.style.fontFamily = 'sans-serif';
            newElementtext.setAttributeNS(null, 'fill', 'white');
            if (planner.view === 'gesamtes Semester' || planner.view === 'entire semester') {
                newElementtext.textContent = date.toLocaleString(planner.locale, {year: '2-digit', month: 'short'});
            } else {
                newElementtext.textContent = date.toLocaleString(planner.locale, {year: 'numeric', month: 'short'});
            }

            svgElement.appendChild(newElementtext);

            date.setMonth(date.getMonth() + 1);
        }
    },
    drawMobile: function(y, date) {
        for (let i = 0; i < planner.months; ++i) {
            // Double loop also for the weeks in the month
            // Figure out the month number
            let weeks = planner.weeksInMonth.get(date.getMonth().toString() + date.getFullYear().toString());
            let weekAmount = weeks[1] - weeks[0] + 1;
            if (weeks[0] === 52) {
                weekAmount = 6;
            }
            for (let j = 0; j < weekAmount; j++) {
                // Also figure out first and last day of the week
                const year = date.getFullYear();
                let week = 0;
                if (weeks[0] === 52) {
                    week = (j === 0 ? 52 : j);
                } else {
                    week = weeks[0] + j;
                }
                // 4th Jan is always in first week
                let firstDate;
                if (weeks[0] === 52) {
                    // Jan handled differnetly, needs to be dates from last year
                    firstDate = new Date(year - 1, 0, 4);
                } else {
                    firstDate = new Date(year, 0, 4);
                }
                let firstDateDay = firstDate.getDay();
                // Make sure the week starts with Monday
                if (firstDateDay === 0) {
                    firstDateDay = 6;
                } else {
                    firstDateDay = firstDateDay - 1;
                }

                const firstDay = new Date(firstDate.setDate(firstDate.getDate() + (week - 1) * 7 - firstDateDay));
                const lastDay = new Date(firstDate.setDate(firstDate.getDate() + 6));
                // Inserting line for each month
                const newElementline = document.createElementNS(NS, 'rect');
                const x = planner.paddingleft + i * planner.monthWidth + i * planner.padding;
                const x1 = planner.paddingleft + (i + 1) * planner.monthWidth + i * planner.padding;
                const width = x1 - x;
                const daywith = width / 7;
                const height = planner.barHeight;
                newElementline.setAttributeNS(null, 'x', x);
                newElementline.setAttributeNS(null, 'width', width);
                newElementline.setAttributeNS(null, 'y', y + j * planner.weekHeight);
                newElementline.setAttributeNS(null, 'height', height);
                newElementline.setAttributeNS(null, 'rx', 5);
                newElementline.setAttributeNS(null, 'stroke', '#000');
                svgElement.appendChild(newElementline);
                // If first or last week, show where actual month starts
                if (j === 0) {
                    // How many days are of the previous month
                    if (firstDay.getMonth() !== lastDay.getMonth()) {
                        // Means the previous month is still in this week
                        let diff = new Date(lastDay.getFullYear(), lastDay.getMonth(), 1).getTime()
                            - new Date(firstDay.getFullYear(), firstDay.getMonth(), firstDay.getDate()).getTime();
                        diff = diff / (1000 * 3600 * 24);
                        // Inserting line for previous month to overlay
                        const pastMonthLine = document.createElementNS(NS, 'rect');
                        const x = planner.paddingleft + i * planner.monthWidth + i * planner.padding;
                        const x1 = x + diff * daywith;
                        const width = x1 - x;
                        const height = planner.barHeight;
                        pastMonthLine.setAttributeNS(null, 'x', x);
                        pastMonthLine.setAttributeNS(null, 'width', width);
                        pastMonthLine.setAttributeNS(null, 'y', y + j * planner.weekHeight);
                        pastMonthLine.setAttributeNS(null, 'height', height);
                        pastMonthLine.setAttributeNS(null, 'rx', 0);
                        pastMonthLine.setAttributeNS(null, 'fill', 'white');
                        pastMonthLine.style.opacity = '0.8';
                        svgElement.appendChild(pastMonthLine);
                    }

                } else if (j === (weekAmount - 1)) {
                    // Means we are in the last week of the month
                    if (firstDay.getMonth() !== lastDay.getMonth()) {
                        // Means the previous month is still in this week
                        let diff = new Date(lastDay.getFullYear(), lastDay.getMonth(), lastDay.getDate()).getTime()
                            - new Date(lastDay.getFullYear(), lastDay.getMonth(), 1).getTime();
                        diff = (diff / (1000 * 3600 * 24)) + 1;
                        // These many days of the next month are visible in this month
                        // Inserting line for previous month to overlay
                        const nextMonthLine = document.createElementNS(NS, 'rect');
                        const x1 = planner.paddingleft + (i + 1) * planner.monthWidth + i * planner.padding;
                        const x = x1 - diff * daywith;
                        const width = x1 - x;
                        const height = planner.barHeight;
                        nextMonthLine.setAttributeNS(null, 'x', x);
                        nextMonthLine.setAttributeNS(null, 'width', width);
                        nextMonthLine.setAttributeNS(null, 'y', y + j * planner.weekHeight);
                        nextMonthLine.setAttributeNS(null, 'height', height);
                        nextMonthLine.setAttributeNS(null, 'rx', 0);
                        nextMonthLine.setAttributeNS(null, 'fill', 'white');
                        nextMonthLine.style.opacity = '0.8';
                        svgElement.appendChild(nextMonthLine);
                    }
                }

                for (let i = 1; i < 7; i++) {
                    const weekline = document.createElementNS(NS, 'line');
                    weekline.setAttributeNS(null, 'x1', x + i * daywith);
                    weekline.setAttributeNS(null, 'y1', y + j * planner.weekHeight);
                    weekline.setAttributeNS(null, 'x2', x + i * daywith);
                    weekline.setAttributeNS(null, 'y2', y + j * planner.weekHeight + height);
                    weekline.style.stroke = planner.primaryMarkerColour;
                    weekline.style.strokeWidth = '3';
                    weekline.style.opacity = '1';
                    svgElement.appendChild(weekline);
                }
                // Inserting text into line
                const newElementtext = document.createElementNS(NS, 'text');
                const textx = planner.paddingleft + i * planner.padding + i * planner.monthWidth + planner.monthWidth / 2;
                newElementtext.setAttributeNS(null, 'x', textx);
                newElementtext.setAttributeNS(null, 'y', (y + height / 2) + j * planner.weekHeight);
                newElementtext.setAttributeNS(null, 'dy', '6px');
                newElementtext.setAttributeNS(null, 'cursor', 'pointer');
                newElementtext.style.textAnchor = 'middle';
                newElementtext.style.fontWeight = 'bold';
                newElementtext.style.fontFamily = 'sans-serif';
                newElementtext.setAttributeNS(null, 'fill', 'white');
                newElementtext.textContent += planner.strings.calendarweek + ' '
                    + (week)
                    + ' ('
                    + firstDay.toLocaleDateString(planner.locale, {day: 'numeric', month: 'numeric'})
                    + ' - '
                    + lastDay.toLocaleDateString(planner.locale, {day: 'numeric', month: 'numeric'})
                    + ' )';
                svgElement.appendChild(newElementtext);
            }
            date.setMonth(date.getMonth() + 1);
        }

    },
    weekLevel: function(weeks, calendarWeek) {
        let mult = 0;
        if (weeks[0] !== 52) {
            for (let first = weeks[0]; first <= weeks[1]; first++) {
                if (calendarWeek === first) {
                    mult = first - weeks[0];
                }
            }
        } else {
            // We need to calculate differently if it is January
            if (calendarWeek === 52) {
                mult = 0;
            } else {
                for (let first = 1; first <= weeks[1]; first++) {
                    if (calendarWeek === first) {
                        mult = first;
                    }
                }
            }
        }
        return mult;
    },

    inNextMonthMult: function(nextWeeks, calendarWeek) {
        let nextMult = 0;
        if (nextWeeks[0] === 52) {
            // Overflowing into the next year, need to handle differently
            if (calendarWeek === 52) {
                nextMult = 0;
            } else {
                for (let first = 1; first <= nextWeeks[1]; first++) {
                    if (calendarWeek === first) {
                        nextMult = first;
                    }
                }
            }
        } else {
            for (let first = nextWeeks[0]; first <= nextWeeks[1]; first++) {
                if (calendarWeek === first) {
                    nextMult = first - nextWeeks[0];
                }
            }
        }
        return nextMult;
    },

    inPastMonthMult: function(pastWeeks, calendarWeek) {
        let pastMult = 0;
        for (let first = pastWeeks[0]; first <= pastWeeks[1]; first++) {
            if (calendarWeek === first) {
                pastMult = first - pastWeeks[0];
            }
        }
        return pastMult;
    },

    drawSecondTitleLine: function(item, line2, firstline, hover, hoverBack) {
        if (item.title.length > 25) {
            // Title needs to go on two lines
            line2 = document.createElementNS(NS, 'tspan');
            line2.setAttributeNS(null, 'x', (parseInt(hover.getAttribute('x'))).toString());
            line2.setAttributeNS(null, 'dy', '1em');
            line2.setAttributeNS(null, 'class', 'planner-eventTitleTooltip');
            const words = firstline.textContent.split(' ');
            let line1 = '';
            let line2text = '';
            for (let i = 0; i < words.length; i++) {
                let test = line1 + words[i] + ' ';
                let testLength = firstline.textContent.substring(0, test.length).length;
                if (testLength > 25 && i > 0) {
                    line2text = words.slice(i).join(' ');
                    break;
                }
                line1 = test;
            }
            line2.innerHTML = line2text;
            firstline.innerHTML = line1;
            hoverBack.setAttributeNS(null, 'height', '60px');
        }
        return line2;
    },

    moveHover: function(hoverBack, hover, firstline, line2, secondline, thirdline, markerDiameter) {
        const xValue = parseInt(hoverBack.getAttributeNS(null, 'x')) + 220;
        if (xValue > svgElement.getAttributeNS(null, 'width')) {
            const difference = 225 + markerDiameter;
            hoverBack.setAttributeNS(null, 'x', (hoverBack.getAttributeNS(null, 'x') - difference).toString());
            hover.setAttributeNS(null, 'x', (hover.getAttributeNS(null, 'x') - difference).toString());
            firstline.setAttributeNS(null, 'x', (firstline.getAttributeNS(null, 'x') - difference).toString());
            if (line2) {
                line2.setAttributeNS(null, 'x', (line2.getAttributeNS(null, 'x') - difference).toString());

            }
            secondline.setAttributeNS(null, 'x', (secondline.getAttributeNS(null, 'x') - difference).toString());
            if (thirdline) {
                thirdline.setAttributeNS(null, 'x', (thirdline.getAttributeNS(null, 'x') - difference).toString());
            }
        }
    },

    addThirdLineHover: function(item, thirdline, hover, hoverBack) {
        if (!item.userid) {
            thirdline = document.createElementNS(NS, 'tspan');
            thirdline.innerHTML = planner.strings.completed_by + ' ' + item.countcompleted +
                ' ' + planner.strings.students;
            thirdline.setAttributeNS(null, 'x',
                (parseInt(hover.getAttribute('x'))).toString());
            thirdline.setAttributeNS(null, 'dy', '1em');
            hover.appendChild(thirdline);
            hoverBack.setAttributeNS(null, 'height',
                (parseInt(hoverBack.getAttributeNS(null, 'height'), 10) + 12).toString() + 'px');
        }
        return thirdline;
    },

    createSecondLineMerge: function(text1, line2) {
        if (text1.textContent.length > 29) {
            // Title needs to go on two lines
            line2 = document.createElementNS(NS, 'tspan');
            line2.setAttributeNS(null, 'x', 0);
            line2.setAttributeNS(null, 'dy', '1em');
            line2.setAttributeNS(null, 'class', 'planner-eventTitleTooltip');
            const words = text1.textContent.split(' ');
            let line1 = '';
            let line2text = '';
            for (let i = 0; i < words.length; i++) {
                let test = line1 + words[i] + ' ';
                let testLength = text1.textContent.substring(0, test.length).length;
                if (testLength > 29 && i > 0) {
                    line2text = words.slice(i).join(' ');
                    break;
                }
                line1 = test;
            }
            line2.innerHTML = line2text;
            text1.textContent = line1;
        }
        return line2;
    },

    drawMarker: function(items) {
        let newElement = null;
        items = items.filter(function(item) {
            return planner.includedTypes.indexOf(item.type) !== -1
                && (item.visible || planner.isteacher)
                && ((item.startdate * 1000) >= planner.startDate.getTime())
                && ((item.startdate * 1000) <= planner.endDate.getTime());
        });
        // Keep track of how many events occur on the same day and which days have the most.
        const stackTracker = {
            events: {},
            milestones: {},
            max: {
                events: 0,
                milestones: 0,
            },
        };
        const mobile = (window.innerWidth < 767);
        let
            count = items.length,
            markerPositions = new Array(count),
            overlapMap = [];

        for (let i = 0; i < count; ++i) {
            const
                item = items[i],
                itemDate = new Date(item.startdate * 1000),
                itemWeek = this.getWeek(itemDate),
                // There was a bug here if the startdate is not on a 1st of the month
                monthOffset = itemDate.getMonth() - planner.startDate.getMonth() +
                    (12 * (itemDate.getFullYear() - planner.startDate.getFullYear())),
                genericMonthCount = new Date(itemDate.getFullYear(), (itemDate.getMonth() + 1), 0).getDate(),
                startDate = new Date(itemDate.getFullYear(), itemDate.getMonth(), 1),
                endDate = new Date(itemDate.getFullYear(), itemDate.getMonth(), genericMonthCount);

            let xPos;
            if (!mobile) {
                xPos = (planner.paddingleft + monthOffset * planner.monthWidth
                    + monthOffset * planner.padding + 2
                    + ((planner.monthWidth - 4) / (endDate.getDate() - startDate.getDate()))
                    * (itemDate.getDate() - startDate.getDate()));
            } else {
                xPos = (itemDate.getDay() === 0) ? ((planner.paddingleft + monthOffset * planner.monthWidth)
                        + monthOffset * planner.padding + 2
                        + ((planner.monthWidth - 4) / 7)
                        * 6
                        + ((planner.weekWidth / 7) / 2))
                    : ((planner.paddingleft + monthOffset * planner.monthWidth)
                        + monthOffset * planner.padding + 2
                        + ((planner.monthWidth - 4) / 7)
                        * (itemDate.getDay() - 1))
                    + ((planner.weekWidth / 7) / 2);
            }
            const
                type = item.userid ? 'milestones' : 'events',
                stackTrack = stackTracker[type],
                currentStackCount = stackTrack[xPos] = (stackTrack[xPos] ?? 0) + 1;
            if (currentStackCount > stackTracker.max[type]) {
                stackTracker.max[type] = currentStackCount;
            }
            markerPositions[i] = {
                x: xPos,
                stackPos: currentStackCount,
                week: itemWeek
            };

        }

        let
            markerDiameter = (mobile) ? 30 : 30, // The width and height of the rectangle, excluding stroke width
            tooltipHeight = markerDiameter,
            needleLength = (mobile) ? 15 : 25, // The length of the stroke connecting a marker with the date strip.
            eventsStartY = (!mobile) ?
                tooltipHeight
                + needleLength - 15
                + stackTracker.max.events * markerDiameter
                : tooltipHeight
                + needleLength - 15
                + markerDiameter, // Only ever stack of 1
            milestonesStartY = (mobile) ?
                eventsStartY
                : eventsStartY + planner.barHeight;

        // Where the month bars start (for mobile, where the first bar starts)
        planner.barPosY = eventsStartY + planner.barHeight / 2;
        // Change SVG height according to the maximum number of events on the same day.
        // Exclude milestones if there are none.
        // Needed for mobile display
        planner.weekHeight = 80;
        if (!mobile) {
            svgHeight = stackTracker.max.milestones === 0
                ? milestonesStartY + 15 // Add some margin.
                : milestonesStartY
                + needleLength - 15
                + stackTracker.max.milestones * markerDiameter
                + tooltipHeight;
        } else {
            svgHeight = planner.weekHeight * 6 + planner.barHeight;// For mobile svgHeight is static
        }
        planner.updateSvgHeight(svgHeight);

        const startDate = new Date();
        const endDate = new Date();
        let index = 0;
        const currentDate = Date.now() / 1000;
        // Function to check if element already exists in overlapMap
        const inOverMap = function(curVal, indx) {
            return !!overlapMap[indx].includes(this);
        };
        // Check for overlaps, only needed for desktop
        // For mobile, overlaps only if more on exact day, can retrieve from maxstacktracker
        overlapMap = this.checkForOverlaps(markerPositions, markerDiameter, overlapMap, inOverMap, items, mobile);
        // Stick with forEach() because it would cause to many grunt errors when changed to a loop :-| .
        items.forEach(function(item) {
            const i = index++;
            let countDrawn = 1;
            let inPastMonth = false;
            let inNextMonth = false;
            // Only draw elements that are not overlapping
            if (!overlapMap.find(inOverMap, markerPositions[i])) {
                startDate.setTime(item.startdate * 1000);
                endDate.setTime(item.enddate * 1000);

                let
                    xPos = markerPositions[i].x,
                    stackPos = markerPositions[i].stackPos;

                let needleStart, needleEnd, yPos, tooltipY;
                let otherneedleStart, otherneedleEnd, otheryPos, othertooltipY;
                if (item.userid && !mobile) { // Check if milestone.
                    needleStart = milestonesStartY;
                    needleEnd = needleStart + needleLength;
                    yPos = needleEnd + markerDiameter * (stackPos - 1) + markerDiameter;
                    tooltipY = needleEnd + (stackTracker.max.milestones - 1) * markerDiameter + tooltipHeight;
                } else if (!mobile) {
                    needleStart = eventsStartY;
                    needleEnd = needleStart - needleLength;
                    yPos = needleEnd - markerDiameter * (stackPos - 1);
                    tooltipY = needleEnd - (stackTracker.max.events - 1) * markerDiameter - tooltipHeight;
                } else {
                    // Drawmarker calculations for mobile view
                    const calendarWeek = planner.getWeek(startDate);
                    const weeks = planner.weeksInMonth.get(startDate.getMonth().toString() + startDate.getFullYear().toString());
                    // On mobile if that calendar week is also in the previous month or in the next,
                    // We need to draw the corresponding elements too
                    const copiedDate = new Date(startDate.getTime());
                    copiedDate.setMonth((copiedDate.getMonth() - 1));

                    const pastWeeks = planner.weeksInMonth.get(copiedDate.getMonth().toString()
                        + copiedDate.getFullYear().toString());
                    copiedDate.setMonth((copiedDate.getMonth() + 2));
                    const nextWeeks = planner.weeksInMonth.get(copiedDate.getMonth().toString()
                        + copiedDate.getFullYear().toString());

                    if (pastWeeks !== undefined && calendarWeek === pastWeeks[1]) {
                        inPastMonth = true;
                        countDrawn++;
                    }
                    if (nextWeeks !== undefined && calendarWeek === nextWeeks[0]) {
                        inNextMonth = true;
                        countDrawn++;
                    }

                    let mult = planner.weekLevel(weeks, calendarWeek);

                    if (inPastMonth) {
                        const pastMult = planner.inPastMonthMult(pastWeeks, calendarWeek);
                        otherneedleStart = eventsStartY + pastMult * planner.weekHeight;
                        otherneedleEnd = otherneedleStart - needleLength;
                        otheryPos = otherneedleEnd;
                        othertooltipY = otherneedleEnd - tooltipHeight;
                    }
                    if (inNextMonth) {
                        const nextMult = planner.inNextMonthMult(nextWeeks, calendarWeek);
                        otherneedleStart = eventsStartY + nextMult * planner.weekHeight;
                        otherneedleEnd = otherneedleStart - needleLength;
                        otheryPos = otherneedleEnd;
                        othertooltipY = otherneedleEnd - tooltipHeight;
                    }
                    needleStart = eventsStartY + mult * planner.weekHeight;
                    needleEnd = needleStart - needleLength;
                    yPos = needleEnd;
                    tooltipY = needleEnd - tooltipHeight;
                }
                const anchor = 'start';

                for (let i = 0; i < countDrawn; i++) {
                    if (i === 1) {
                        if (inPastMonth) {
                            xPos -= ((planner.monthWidth + planner.padding + 2));
                        } else {
                            xPos += ((planner.monthWidth + planner.padding + 2));
                        }
                        needleStart = otherneedleStart;
                        needleEnd = otherneedleEnd;
                        yPos = otheryPos;
                        tooltipY = othertooltipY;
                    }
                    const hoverBack = document.createElementNS(NS, 'rect');
                    hoverBack.setAttributeNS(null, 'x', xPos + (markerDiameter / 2) + 5);
                    hoverBack.setAttributeNS(null, 'fill', 'white');
                    hoverBack.setAttributeNS(null, 'rx', '2');
                    hoverBack.setAttributeNS(null, 'stroke', planner.primaryMarkerColour);
                    hoverBack.style.display = 'none';

                    const hover = document.createElementNS(NS, 'text');
                    hover.setAttributeNS(null, 'class', 'hover');
                    hover.setAttributeNS(null, 'x', xPos + (markerDiameter / 2) + 7);
                    hover.setAttributeNS(null, 'y', (tooltipY - 10));
                    hover.setAttributeNS(null, 'dy', '6px');
                    hover.style.textAnchor = anchor;
                    hover.style.fontSize = '14px';
                    hover.style.fontWeight = 'simple';
                    hover.style.fontFamily = 'sans-serif';
                    hover.style.display = 'none';
                    hover.setAttributeNS(null, 'fill', 'black');

                    let firstline = document.createElementNS(NS, 'tspan');
                    let line2 = null;
                    const secondline = document.createElementNS(NS, 'tspan');
                    let thirdline = null;
                    firstline.innerHTML = item.title;
                    firstline.setAttributeNS(null, 'x', (parseInt(hover.getAttribute('x'))).toString());
                    firstline.setAttributeNS(null, 'dy', '1em');
                    firstline.setAttributeNS(null, 'class', 'planner-eventTitleTooltip');

                    line2 = planner.drawSecondTitleLine(item, line2, firstline, hover, hoverBack);

                    secondline.innerHTML = planner.dateFormatter.format(startDate) + ', '
                        + planner.timeFormatter.format(startDate) + ' – '
                        + planner.timeFormatter.format(endDate);

                    secondline.setAttributeNS(null, 'x', (parseInt(hover.getAttribute('x'))).toString());
                    secondline.setAttributeNS(null, 'dy', '1em');

                    hover.appendChild(firstline);
                    hoverBack.setAttributeNS(null, 'height', '36px');
                    if (line2) {
                        hover.appendChild(line2);
                        hoverBack.setAttributeNS(null, 'height',
                            (parseInt(hoverBack.getAttributeNS(null, 'height'), 10) + 12).toString() + 'px');
                    }
                    hover.appendChild(secondline);

                    thirdline = planner.addThirdLineHover(item, thirdline, hover, hoverBack);

                    // This needs to be responsive based on longest tspan
                    hoverBack.setAttributeNS(null, 'width', '220px');

                    // Making sure hover appears just to the right of the element
                    hover.setAttributeNS(null, 'y', yPos - markerDiameter - 10);
                    hoverBack.setAttributeNS(null, 'y', yPos - markerDiameter - 10);

                    planner.moveHover(hoverBack, hover, firstline, line2, secondline, thirdline, markerDiameter);

                    svgElement.appendChild(hoverBack);
                    svgElement.appendChild(hover);

                    const
                        fill = planner.getColor(item),
                        stroke = item.enddate < currentDate ? fill : planner.stillDueColour;

                    const label = item.type[0];
                    const mandatory = item.mandatory;
                    const graded = item.graded;

                    if (stackPos <= 1 || mobile) {
                        newElement = document.createElementNS(NS, 'line');
                        newElement.setAttributeNS(null, 'x1', xPos);
                        newElement.setAttributeNS(null, 'y1', needleStart);
                        newElement.setAttributeNS(null, 'x2', xPos);
                        newElement.setAttributeNS(null, 'y2', needleEnd);
                        newElement.style.stroke = stroke;
                        newElement.style.strokeWidth = '4';
                        newElement.style.opacity = '1';
                        newElement.setAttributeNS(null, 'cursor', 'pointer');
                        newElement.addEventListener('click', () => {
                            planner.showModal(item);
                        });
                        newElement.addEventListener('mouseover', () => {
                            hover.style.display = null;
                            hoverBack.style.display = null;

                        });
                        newElement.addEventListener('mouseout', () => {
                            hover.style.display = 'none';
                            hoverBack.style.display = 'none';

                        });
                        svgElement.prepend(newElement);

                    }
                    const rectElement = document.createElementNS(NS, 'rect');
                    rectElement.setAttributeNS(null, 'id', 'event_' + item.id);
                    rectElement.setAttributeNS(null, 'x', xPos - (markerDiameter / 2));
                    rectElement.setAttributeNS(null, 'y', yPos - (markerDiameter));
                    rectElement.setAttributeNS(null, 'height', markerDiameter);
                    rectElement.setAttributeNS(null, 'width', markerDiameter);
                    rectElement.setAttributeNS(null, 'rx', 3.5);
                    rectElement.setAttributeNS(null, 'fill', fill);
                    rectElement.style.stroke = stroke;
                    rectElement.style.strokeWidth = '3.5';
                    rectElement.setAttributeNS(null, 'cursor', 'pointer');
                    rectElement.addEventListener('click', () => {
                        planner.showModal(item);
                    });
                    rectElement.addEventListener('mouseover', () => {
                        hover.style.display = null;
                        hoverBack.style.display = null;
                        rectElement.style.opacity = '0.5';

                    });
                    rectElement.addEventListener('mouseout', () => {
                        hover.style.display = 'none';
                        hoverBack.style.display = 'none';
                        rectElement.style.opacity = '1';
                    });

                    newElement.after(rectElement);

                    const textElement = document.createElementNS(NS, 'text');
                    rectElement.after(textElement);
                    textElement.setAttributeNS(null, 'x', xPos);
                    textElement.setAttributeNS(null, 'y', yPos - (markerDiameter / 2));
                    textElement.setAttributeNS(null, 'dy', '6px');
                    if (graded) {
                        textElement.style.cssText += 'text-decoration: underline';
                        textElement.style.textDecorationColor = planner.stillDueColour;
                    }
                    textElement.style.cssText += 'text-anchor:middle;font-weight:bold;font-family:sans-serif';
                    textElement.setAttributeNS(null, 'fill', planner.stillDueColour);
                    textElement.setAttributeNS(null, 'cursor', 'pointer');

                    const text = document.createElementNS(NS, 'tspan');
                    text.textContent = label;
                    text.setAttributeNS(null, 'fill', planner.fontColour);
                    textElement.appendChild(text);
                    if (mandatory) {
                        const asterisk = document.createElementNS(NS, 'tspan');
                        asterisk.setAttributeNS(null, 'style', 'fill:#474747');
                        asterisk.textContent = ' *';
                        textElement.appendChild(asterisk);
                    }

                    textElement.addEventListener('click', () => {
                        planner.showModal(item);
                    });
                    textElement.addEventListener('mouseover', () => {
                        hover.style.display = null;
                        hoverBack.style.display = null;
                        rectElement.style.opacity = '0.5';

                    });
                    textElement.addEventListener('mouseout', () => {
                        hover.style.display = 'none';
                        hoverBack.style.display = 'none';
                        rectElement.style.opacity = '1';

                    });

                }

            }
        });
        // Now we have information about which elements overlap, draw those
        overlapMap.forEach(function(overlap) {
            // Need to set startdate here too
            startDate.setTime(items[markerPositions.indexOf(overlap[0])].startdate * 1000);
            endDate.setTime(items[markerPositions.indexOf(overlap[0])].enddate * 1000);
            const
                fill = (items[markerPositions.indexOf(overlap[0])].userid && !mobile)
                    ? planner.milestoneColour
                    : planner.primaryMarkerColour;
            // Ypos will always be just height of one element, since we merge stacked ones
            let needleStart, needleEnd, yPos;
            let inPastMonth = false;
            let otherneedleStart, otherneedleEnd, otheryPos;
            let countDrawn = 1;
            let oneMandatory = false;
            let oneGraded = false;

            if (items[markerPositions.indexOf(overlap[0])].userid && !mobile) { // Check if milestone.
                needleStart = milestonesStartY;
                needleEnd = needleStart + needleLength;
                yPos = needleEnd + markerDiameter;
            } else {
                // Don't need to differentiate between mobile and desktop here
                const calendarWeek = planner.getWeek(new Date(items[markerPositions.indexOf(overlap[0])].startdate * 1000));
                const weeks = planner.weeksInMonth.get(startDate.getMonth().toString() + startDate.getFullYear().toString());
                // On mobile if that calendar week is also in the previous month or in the next,
                // We need to draw the corresponding elements too
                let copiedDate = new Date(startDate.getTime());
                copiedDate.setMonth(copiedDate.getMonth() - 1);
                const pastWeeks = planner.weeksInMonth.get(copiedDate.getMonth().toString() + copiedDate.getFullYear().toString());
                copiedDate.setMonth(copiedDate.getMonth() + 2);
                const nextWeeks = planner.weeksInMonth.get(copiedDate.getMonth().toString() + copiedDate.getFullYear().toString());
                let mult = 0;
                if (mobile) {
                    for (let first = weeks[0]; first <= weeks[1]; first++) {
                        if (calendarWeek === first) {
                            mult = first - weeks[0];
                        }
                    }
                    if (pastWeeks !== undefined && calendarWeek === pastWeeks[1]) {
                        inPastMonth = true;
                        countDrawn++;
                        const pastMult = planner.pastMergeMobile(weeks, pastWeeks, calendarWeek);
                        otherneedleStart = eventsStartY + pastMult * planner.weekHeight;
                        otherneedleEnd = otherneedleStart - needleLength;
                        otheryPos = otherneedleEnd;

                    }
                    if (nextWeeks !== undefined && calendarWeek === nextWeeks[0]) {
                        countDrawn++;
                        const nextMult = planner.nextMergeMobile(weeks, nextWeeks, calendarWeek);
                        otherneedleStart = eventsStartY + nextMult * planner.weekHeight;
                        otherneedleEnd = otherneedleStart - needleLength;
                        otheryPos = otherneedleEnd;
                    }
                }
                needleStart = eventsStartY + mult * planner.weekHeight;
                needleEnd = needleStart - needleLength;
                yPos = needleEnd;
            }
            overlap.sort((a, b) => a.x - b.x);
            let stroke;
            let latestDate;
            let xPos = overlap[0].x;
            let xPosEnd = overlap[overlap.length - 1].x;

            let width = (xPosEnd + markerDiameter / 2) - (xPos - (markerDiameter / 2));
            let center = xPos - markerDiameter / 2 + width / 2;

            const links = [];
            // Draw each line to also show the amount of elements in there and roughly their date
            overlap.forEach(function(element) {
                const idx = markerPositions.indexOf(element);
                startDate.setTime(items[idx].startdate * 1000);
                endDate.setTime(items[idx].enddate * 1000);
                latestDate = items[idx].enddate;
                const lineStroke = items[idx].enddate < currentDate ? fill : planner.stillDueColour;

                if (items[idx].mandatory) {
                    oneMandatory = true;
                }
                if (items[idx].graded) {
                    oneGraded = true;
                }

                const stackPos = element.stackPos;
                const xPosEl = element.x;
                // Creating the correct entry for the hovering later
                const textWrapper = document.createElementNS(NS, 'svg');
                const text = document.createElementNS(NS, 'text');
                text.setAttributeNS(null, 'x', 0);
                text.setAttributeNS(null, 'y', 0);

                const text0 = document.createElementNS(NS, 'tspan');
                text0.setAttributeNS(null, 'x', 0);
                text0.setAttributeNS(null, 'dy', '1em');
                text0.setAttributeNS(null, 'class', 'planner-eventTitleTooltip');
                text0.textContent = items[idx].mandatory ? items[idx].type[0] + '*' : items[idx].type[0];
                if (items[idx].graded) {
                    text0.setAttributeNS(null, 'text-decoration', 'underline');
                }
                text.appendChild(text0);

                const text1 = document.createElementNS(NS, 'tspan');
                text1.textContent = ': ' + items[idx].title;
                text1.setAttributeNS(null, 'class', 'planner-eventTitleTooltip');
                let line2 = null;
                line2 = planner.createSecondLineMerge(text1, line2);

                text.appendChild(text1);
                textWrapper.setAttributeNS(null, 'height', '55px');
                if (line2) {
                    text.appendChild(line2);
                    textWrapper.setAttributeNS(null, 'height', '70px');
                }

                const text2 = document.createElementNS(NS, 'tspan');
                text2.setAttributeNS(null, 'x', 0);
                text2.setAttributeNS(null, 'dy', '1em');
                text2.innerHTML += planner.dateFormatter.format(startDate) + ', '
                    + planner.timeFormatter.format(startDate) + ' – '
                    + planner.timeFormatter.format(endDate);
                text.appendChild(text2);
                let text3 = null;
                if (!items[idx].userid) {
                    text3 = document.createElementNS(NS, 'tspan');
                    text3.setAttributeNS(null, 'x', 0);
                    text3.setAttributeNS(null, 'dy', '1em');
                    text3.innerHTML += planner.strings.completed_by + ' '
                        + items[idx].countcompleted + ' '
                        + planner.strings.students;
                    text.appendChild(text3);
                }

                textWrapper.appendChild(text);

                textWrapper.setAttributeNS(null, 'width', '100%');

                const dropdownLink = document.createElement('a');
                dropdownLink.appendChild(textWrapper);
                dropdownLink.setAttribute('class', 'dropdown-item');
                dropdownLink.addEventListener('click', () => {
                    // This should now open the modal
                    planner.showModal(items[idx]);
                });
                // Temporarily push the created elements in array to evoke later on
                links.push(dropdownLink);

                if (stackPos <= 1 || mobile) {
                    newElement = document.createElementNS(NS, 'line');
                    newElement.setAttributeNS(null, 'x1', xPosEl);
                    newElement.setAttributeNS(null, 'y1', needleStart);
                    newElement.setAttributeNS(null, 'x2', xPosEl);
                    newElement.setAttributeNS(null, 'y2', needleEnd);
                    newElement.style.stroke = lineStroke;
                    newElement.style.strokeWidth = '4';
                    newElement.setAttributeNS(null, 'cursor', 'pointer');
                    svgElement.prepend(newElement);
                }
            });
            // Stroke colour is dependent on the last date in the overlap block
            stroke = latestDate < currentDate ? fill : planner.stillDueColour;
            for (let i = 0; i < countDrawn; i++) {
                // Create the actual rectangle
                const rectElement = document.createElementNS(NS, 'rect');
                rectElement.setAttributeNS(null, 'id', 'overlap_' + Math.floor(overlap[0].x));
                if (i === 1) {
                    if (inPastMonth) {
                        xPos -= (planner.monthWidth + planner.padding + 2);
                        center -= (planner.monthWidth + planner.padding + 2);
                        xPosEnd -= (planner.monthWidth + planner.padding + 2);
                    } else {
                        xPos += (planner.monthWidth + planner.padding + 2);
                        center += (planner.monthWidth + planner.padding + 2);
                        xPosEnd += (planner.monthWidth + planner.padding + 2);
                    }
                    needleStart = otherneedleStart;
                    needleEnd = otherneedleEnd;
                    yPos = otheryPos;
                    const newElement = document.createElementNS(NS, 'line');
                    newElement.setAttributeNS(null, 'x1', xPos);
                    newElement.setAttributeNS(null, 'y1', needleStart);
                    newElement.setAttributeNS(null, 'x2', xPos);
                    newElement.setAttributeNS(null, 'y2', needleEnd);
                    newElement.style.stroke = stroke;
                    newElement.style.strokeWidth = '4';
                    newElement.setAttributeNS(null, 'cursor', 'pointer');
                    svgElement.prepend(newElement);
                    rectElement.setAttributeNS(null, 'id', 'overlap_' + Math.floor(overlap[0].x) + '2');
                }

                rectElement.setAttributeNS(null, 'class', 'merge');
                // Need to add each dropdown to the widget as can't append to an svg
                const positionX = xPos - (markerDiameter / 2);
                const positionY = yPos - (markerDiameter);

                rectElement.setAttributeNS(null, 'x', positionX.toString());
                rectElement.setAttributeNS(null, 'y', positionY.toString());
                rectElement.setAttributeNS(null, 'height', markerDiameter.toString());
                rectElement.setAttributeNS(null, 'width', width.toString());
                rectElement.setAttributeNS(null, 'rx', 3.5);
                rectElement.setAttributeNS(null, 'fill', fill);
                rectElement.style.stroke = stroke;
                rectElement.style.strokeWidth = '3.5';
                rectElement.setAttributeNS(null, 'cursor', 'pointer');
                rectElement.addEventListener('click', (event) => {
                    planner.openPopup(links, event.clientX);
                });
                rectElement.addEventListener('mouseover', () => {
                    rectElement.style.opacity = '0.5';
                });
                rectElement.addEventListener('mouseout', () => {
                    rectElement.style.opacity = '1';
                });
                newElement.after(rectElement);

                // Now put in the number and potentially whether it is mandatory or not
                const label = overlap.length;
                const textElement = document.createElementNS(NS, 'text');
                rectElement.after(textElement);
                textElement.setAttributeNS(null, 'x', center.toString());
                textElement.setAttributeNS(null, 'y', yPos - (markerDiameter / 2));
                textElement.setAttributeNS(null, 'dy', '6px');
                textElement.setAttributeNS(null, 'class', 'merge');
                textElement.style.cssText += 'text-anchor:middle;font-weight:bold;font-family:sans-serif';
                textElement.setAttributeNS(null, 'fill', planner.stillDueColour);

                const text = document.createElementNS(NS, 'tspan');
                text.textContent = '#' + label;
                text.style.fill = planner.fontColour;
                text.setAttributeNS(null, 'class', 'merge');
                textElement.appendChild(text);
                if (oneMandatory) {
                    const asterisk = document.createElementNS(NS, 'tspan');
                    asterisk.style.fill = planner.stillDueColour;
                    asterisk.textContent = ' *';
                    asterisk.setAttributeNS(null, 'class', 'merge');
                    textElement.appendChild(asterisk);
                }
                if (oneGraded) {
                    textElement.setAttributeNS(null, 'text-decoration', 'underline');
                    textElement.style.textDecorationColor = planner.stillDueColour;
                }
                textElement.setAttributeNS(null, 'cursor', 'pointer');
                textElement.addEventListener('click', (event) => {
                    planner.openPopup(links, event.clientX);
                });
                textElement.addEventListener('mouseover', () => {
                    rectElement.style.opacity = '0.5';
                });
                textElement.addEventListener('mouseout', () => {
                    rectElement.style.opacity = '1';
                });
            }
        });

    },
    openPopup: function(links, mousePosX) {
        // Now need to add all link elements to the popup
        const popup = document.getElementById('planner-mergePopup');
        // Remove elements that were in there before
        popup.innerHTML = '';
        // Based on the links, create all dropdown elements
        popup.style.display = 'block';
        // Change xpos of popup based on where the mouse is, for desktop view only
        if (window.innerWidth > 767) {
            if (mousePosX < window.innerWidth / 2) {
                // Merge element is on the left half of the screen
                popup.style.left = (mousePosX - 50).toString() + 'px';
            } else {
                // Merge element is on the right half of the screen
                popup.style.left = (mousePosX - 275 - 50).toString() + 'px';
            }
        } else {
            popup.style.left = (window.innerWidth / 2 - 132).toString() + 'px';
        }
        links.forEach(function(link) {
            const wrapper = document.createElement('div');
            wrapper.setAttribute('class', 'planner-popupDropdownItem');
            wrapper.appendChild(link);
            popup.appendChild(wrapper);
        });
    },

    pastMergeMobile: function(weeks, pastWeeks, calendarWeek) {
        let pastMult = 0;
        for (let first = pastWeeks[0]; first <= pastWeeks[1]; first++) {
            if (calendarWeek === first) {
                pastMult = first - pastWeeks[0];
            }
        }
        return pastMult;
    },

    nextMergeMobile: function(weeks, nextWeeks, calendarWeek) {
        let nextMult = 0;
        for (let first = nextWeeks[0]; first <= nextWeeks[1]; first++) {
            if (calendarWeek === first) {
                nextMult = first - nextWeeks[0];
            }
        }
        return nextMult;
    },
    closePopup: function() {
        const popup = document.getElementById('planner-mergePopup');
        popup.style.display = 'none';
    },
    // Takes event data (as fetched from backend), and returns a hex code.
    getColor: (() => {
        const
            now = Date.now() / 1000,
            threshold = now + 604800; // Seven days in seconds.
        return event => {
            const end = event.enddate;
            return (event.completed && planner.completedColour) // Green.
                || (event.userid && planner.milestoneColour) // Student milestones → red;
                || (end < now && planner.primaryMarkerColour) // Teacher events → grey.
                || (end <= threshold && planner.thresholdColour) // Yellow/Orange
                || planner.primaryMarkerColour; // Grey.
        };
    })(),

    checkForOverlaps: function(markerPositions, markerDiameter, overlapMap, inOverMap, items, mobile) {
        const inOverMapCheck = function(curVal, indx) {
            return !!overlapMap[indx].includes(this);
        };
        for (let i = 0; i < markerPositions.length; i++) {
            const occurences = [];
            const iWeek = planner.getWeek(new Date(items[i].startdate * 1000));
            for (let j = 0; j < i; j++) {
                // Flag to determine overlap and add to array
                let found = false;
                const jWeek = planner.getWeek(new Date(items[j].startdate * 1000));

                // Can't check for same week as that is absolutely ok
                if (markerPositions[i].x <= markerPositions[j].x
                    && (items[i].userid === items[j].userid)
                ) {
                    if ((!((markerPositions[i].x + markerDiameter / 2) < (markerPositions[j].x - markerDiameter / 2))) &&
                        (!(mobile && jWeek !== iWeek))) {
                        found = true;
                    }
                } else if (markerPositions[i].x > markerPositions[j].x
                    && (items[i].userid === items[j].userid)) {
                    if ((!((markerPositions[i].x - markerDiameter / 2) > (markerPositions[j].x + markerDiameter / 2))) &&
                        (!(mobile && jWeek !== iWeek))) {
                        found = true;
                    }
                } else if (markerPositions[i].x === markerPositions[j].x && mobile && (iWeek === jWeek)) {
                    // This is to find milestones, as in mobile they go on the same one
                    found = true;
                }
                planner.overlapPushOccurences(overlapMap, inOverMapCheck,
                    markerPositions[j], markerPositions[i],
                    occurences, found);
            }
            if (occurences.length > 1) {
                const overlapMerge = [];
                for (let i = 0; i < occurences.length; i++) {
                    overlapMap[occurences.at(i)].forEach(function(occ) {
                        overlapMerge.push(occ);
                    });
                }
                // Remove the duplicate values
                for (let j = 1; j < occurences.length; j++) {
                    const index = overlapMerge.indexOf(markerPositions[i]);
                    overlapMerge.splice(index, 1);
                }
                // Now remove these from the overlapmap
                overlapMap = overlapMap.reduce((acc, value, index) => {
                    if (!occurences.includes(index)) {
                        acc.push(value);
                    }
                    return acc;
                }, []);
                overlapMap.push(overlapMerge);
                occurences.length = 0;
            }
        }
        // Remove items again if it is only a simple stacking, not a merge
        if (!mobile) {
            const samePos = [];
            overlapMap.forEach(function(overlap, idx) {
                let same = true;
                overlap.forEach(function(el, idx) {
                    if (overlap[idx].x !== overlap[0].x) {
                        same = false;
                    }
                });
                if (same === true) {
                    samePos.push(idx);
                }
            });
            overlapMap = overlapMap.reduce((acc, value, index) => {
                if (!samePos.includes(index)) {
                    acc.push(value);
                }
                return acc;
            }, []);
        }
        return overlapMap;
    },

    overlapPushOccurences: function(overlapMap, inOverMapCheck, markerPosj, markerPosi, occurences, found) {
        if (found) {
            const result = overlapMap.findIndex(inOverMapCheck, markerPosj);
            if (result >= 0) {
                if (!overlapMap[result].includes(markerPosi)) {
                    overlapMap[result].push(markerPosi);
                    occurences.push(result);
                }
            } else {
                occurences.push(overlapMap.push([markerPosi, markerPosj]) - 1);
            }
        }
    },
    drawLegend: function(items) {
        const svgWidth = svgcontainer?.offsetWidth ?? 600;
        const mobile = (window.innerWidth < 767);

        plannerLegend.setAttribute('width', svgWidth);

        const info = document.createElement('div');
        info.setAttribute('id', 'planner-info');

        const infoButton = document.createElement('button');
        infoButton.setAttribute('id', 'planner-infoMobile');
        infoButton.setAttribute('type', 'button');
        infoButton.setAttribute('class', 'planner-checkBtn');
        infoButton.addEventListener('click', () => {
            info.style.left = '0';
        });
        infoButton.addEventListener('mouseover', () => {
            infoButton.style.opacity = '0.5';
        });
        infoButton.addEventListener('mouseout', () => {
            infoButton.style.opacity = '1';
        });
        infoButton.innerText = 'Info';

        if (!mobile) {
            plannerLegend.appendChild(infoButton);
            plannerLegend.appendChild(info);
        } else {
            // Append button and info to options section on mobile
            plannerOptions.appendChild(infoButton);
            plannerOptions.appendChild(info);
        }

        const start = planner.startDate.toLocaleString(planner.locale, {
            year: 'numeric',
            month: 'numeric',
            day: 'numeric'
        });
        const end = planner.endDate.toLocaleString(planner.locale, {year: 'numeric', month: 'numeric', day: 'numeric'});

        const legend = {};
        const marker = {};

        items.forEach(function(item) {
            const newdate = new Date(item.startdate * 1000);
            if (newdate <= planner.endDate && (planner.isteacher || item.visible)) {
                if (!(item.type in legend)) {
                    legend[item.type] = 0;
                }
                if (newdate < start || newdate > end) {
                    return;
                }
                legend[item.type] = legend[item.type] + 1;
                marker[item.type] = item.marker;
            }
        });
        const filter = [];
        let innerHTML = '';
        for (let prop in legend) {
            if (legend[prop] !== 0) {
                filter.push(prop);
                if (Object.prototype.hasOwnProperty.call(legend, prop)) {
                    const propActual = eventStrings[prop] ?? prop;
                    innerHTML += '<div class="d-inline-block" style="padding: 5px 10px; font-size: 16px; width: 220px;">'
                        + '<div class="d-inline-block font-weight-bold text-center" style="width: 32px; height: 32px;'
                        + 'line-height: 28px; border-radius: 20%; border: 4px solid #a09e9e; color: #fff;'
                        + 'background: #a09e9e; margin-right: 6px;">'
                        + prop[0] + '</div>' + legend[prop] + ' ' + propActual + '</div>';
                }
            }
        }
        innerHTML += '<p class=\'ml-3 mb-0\' style=\'font-size: 0.85em;\'>' + planner.strings.legend + '</p>';
        planner.includeTypes = filter;
        if (planner.includedTypes === null) {
            planner.includedTypes = planner.includeTypes;
        }
        info.innerHTML = innerHTML;
    },

    showModal: function(item) {
        if (planner.isteacher) {
            log('OPEN', 'EVENT', null, item.id);
            planner.createEventModal(item);
        } else {
            if ('userid' in item && 'completed' in item) {
                // TODO add info for log plugin.
                log('OPEN', 'MILESTONE', null, item.id);
                planner.createMilestoneModal(item);

            } else {
                log('OPEN', 'EVENT', null, item.id);
                planner.createEventCompletedModal(item);
            }
        }
    },

    createMenu: function() {
        if (plannerMenu !== null) {
            svgWidth = plannerMenu.offsetWidth;
        }
        plannerMenu.setAttribute('width', svgWidth);

        const form = document.createElement('form');
        form.addEventListener('click', (event) => {
            event.stopPropagation(); // Needed to make sure the form does not disappear when a checkbox in it is clicked
        });
        form.setAttribute('id', 'menuform');
        form.setAttribute('style', 'font-size: 16px; font-weight: normal; font-famiy: sans-serif');
        planner.includeTypes.forEach(function(item) {
            const input = document.createElement('input');
            const label = document.createElement('label');
            label.setAttribute('for', item);
            label.innerText = item;
            input.setAttribute('type', 'checkbox');
            input.setAttribute('value', item);
            input.setAttribute('id', item);
            if (planner.includedTypes.includes(item)) {
                input.checked = true;
            }
            input.addEventListener('change', () => {
                if (planner.includedTypes.includes(item)) {
                    log('DESELECT', 'FILTER', item);
                    planner.includedTypes = planner.includedTypes.filter(function(el) {
                        return el !== item;
                    });
                } else {
                    log('SELECT', 'FILTER', item);
                    planner.includedTypes.push(item);
                }
                planner.update();
            });

            label.appendChild(input);
            form.appendChild(label);
        });

        // Create button that will appear for mobile view
        let mobilebutton = document.createElement('button');
        mobilebutton.setAttribute('type', 'button');
        mobilebutton.setAttribute('id', 'planner-check');
        mobilebutton.setAttribute('class', 'planner-checkBtn');
        mobilebutton.addEventListener('click', () => {
            form.style.left = '0';
        });
        mobilebutton.addEventListener('mouseover', () => {
            mobilebutton.style.opacity = '0.5';
        });
        mobilebutton.addEventListener('mouseout', () => {
            mobilebutton.style.opacity = '1';
        });
        mobilebutton.innerText = planner.strings.options;
        plannerOptions.appendChild(mobilebutton);

        const actionForm = document.createElement('form');
        if (!planner.isteacher) {
            actionForm.appendChild(planner.addMilestoneBtn());
        } else {
            actionForm.appendChild(planner.addNotificationCourseSettingsBtn());
            actionForm.appendChild(planner.addEventBtn());
        }
        // Best to attach the view selection button to the form here, add to options form
        actionForm.appendChild(planner.addViewSelectionDrpdwn());
        // Append to either actions or options to allow separation
        plannerOptions.appendChild(form);
        plannerActions.appendChild(actionForm);
    },

    resetModal: function() {
        require(['core_form/changechecker'], function(ChangeChecker) {
            ChangeChecker.startWatching();
            ChangeChecker.resetAllFormDirtyStates();
        });

        // Reload that changes in view are done and a new fresh modal is created.
        planner.update();
    },

    checkDateandTime: function(MandatoryFlag, e, modal) {
        let startdate;
        let enddate;
        for (let i = 0; i <= 5; i++) {
            if (i === 0) {
                startdate = 'startdate';
                enddate = 'enddate';
            } else {
                startdate = 'date' + i;
                enddate = 'endtime' + i;
            }
            const eventDate = new Date(document.getElementById('id_' + startdate + '_year').value,
                document.getElementById('id_' + startdate + '_month').value - 1,
                document.getElementById('id_' + startdate + '_day').value);
            const timestampofevent = eventDate.getTime();
            if ((timestampofevent / 1000) < (planner.startDate.getTime() / 1000)
                || (timestampofevent / 1000) > (planner.endDate.getTime / 1000)) {
                MandatoryFlag = false;
                e.preventDefault();
                const timeoutofrange = getString('timeoutofrange', 'lytix_planner');
                timeoutofrange.then(function(localizedString) {
                    modal.setBody(modal.getBody().innerHTML);
                    modal.getBody().append(localizedString);
                    return localizedString;
                }).catch((error) => {
                    Log.warn(error);
                    throw error;
                });
            }
            const starthour = parseInt(document.getElementById('id_' + startdate + '_hour').value);
            const endhour = parseInt(document.getElementById('id_' + enddate + 'hour').value);
            const endminute = parseInt(document.getElementById('id_' + enddate + 'minute').value);
            const startminute = parseInt(document.getElementById('id_' + startdate + '_minute').value);
            if ((endhour < starthour) || (starthour === endhour && endminute < startminute)) {
                MandatoryFlag = false;
                e.preventDefault();
                const timesmaller = getString('time_smaller', 'lytix_planner');
                timesmaller.then(function(localizedString) {
                    modal.setBody(modal.getBody().innerHTML);
                    modal.getBody().append(localizedString);
                    return localizedString;
                }).catch((error) => {
                    Log.warn(error);
                    throw error;
                });
            }
            if (document.getElementById('id_moreevents').value == 1) {
                break;
            }
        }
        return MandatoryFlag;
    },

    createEventModal: function(item) {
        const form = loadFragment('lytix_planner', 'new_event_form', planner.contextid, item);
        const title = planner.strings.event;
        let m;
        if (Number(M.cfg.version) < 2024042200) {
            m = ModalFactory.create({
                type: ModalType,
                title: title,
                body: form,
            });
        } else {
            m = ModalType.create({
                title: title,
                body: form,
            });
        }
        m.then(function(modal) {
            // Forms are big, we want a big modal.
            modal.setLarge();
            modal.show();
            const root = modal.getRoot();
            root.on(ModalEvents.save, function(e) {
                // Convert all the form elements values to a serialised string.
                const formData = root.find('form').serialize();
                let MandatoryFlag = true;
                if (document.getElementById('id_title').value === "") {
                    MandatoryFlag = false;
                    e.preventDefault();
                }
                MandatoryFlag = planner.checkDateandTime(MandatoryFlag, e, modal);
                const selectedOption =
                    document.getElementById('id_type').options[document.getElementById('id_type').selectedIndex];
                // Check mandatory fields.
                if (selectedOption.text === "Other") {
                    MandatoryFlag = false;
                    if (document.getElementById('id_select_other_german').value === '' ||
                        document.getElementById('id_select_other_english').value === '') {
                        e.preventDefault();
                        modal.setBody(modal.getBody().innerHTML);
                        modal.getBody().append(planner.strings.type_required);
                    } else {
                        if (planner.includeTypes.includes(document.getElementById('id_select_other_german').value) ||
                            planner.includeTypes.includes(document.getElementById('id_select_other_english').value)) {
                            e.preventDefault();
                            modal.setBody(modal.getBody().innerHTML);
                            modal.getBody().append(planner.strings.type_exists);
                        } else {
                            MandatoryFlag = true;
                        }
                    }
                }
                if (MandatoryFlag) {
                    // Call the webservice with formData as param.
                    const promises = Ajax.call([
                        {
                            methodname: 'local_lytix_lytix_planner_event',
                            args: {
                                contextid: planner.contextid,
                                jsonformdata: JSON.stringify(formData)
                            },
                        }
                    ]);
                    promises[0].then(function() {
                        if (!planner.includedTypes.includes(selectedOption.text)) {
                            planner.includedTypes.push(selectedOption.text);
                            planner.includedTypes = null;
                        }
                        planner.resetModal();
                        planner.update();
                        return;
                    }).catch(function(ex) {
                        // TODO Find solution to show error message in modal.
                        Log.warn(ex);
                        planner.resetModal();
                    });
                }
            });
            root.on('modal-save-delete-cancel:delete', function() {
                const promises = Ajax.call([
                    {
                        methodname: 'local_lytix_lytix_planner_delete_event',
                        args: {
                            contextid: planner.contextid,
                            courseid: planner.courseid,
                            userid: planner.userid,
                            id: item.id
                        },
                    }
                ]);
                promises[0].then(function(response) {
                    if (response.success) {
                        planner.resetModal();
                        planner.update();
                    }
                    return;
                }).catch(function(ex) {
                    Log.warn(ex);
                    planner.resetModal();
                });
            });
            root.on(ModalEvents.hidden, function() {
                log('CLOSE', 'EVENT', null, item.id);
                modal.hide();
                modal.destroy();
            });
            root.on(ModalEvents.cancel, function() {
                planner.resetModal();
            });
            return;
        }).catch(function(ex) {
            Log.warn(ex);
        });
    },

    createEventCompletedModal: function(item) {
        // Set correct userid.
        item.userid = planner.userid;
        item.eventid = item.id;
        const form = loadFragment('lytix_planner', 'new_event_completed_form', planner.contextid, item);

        const title = planner.strings.event_completed;
        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: title,
            body: form,
        }).then(function(modal) {
            // Forms are big, we want a big modal.
            modal.setLarge();
            modal.show();
            const root = modal.getRoot();
            root.on(ModalEvents.save, function() {
                // Convert all the form elements values to a serialised string.
                const formData = root.find('form').serialize();
                // Call the webservice with formData as param.
                const promises = Ajax.call([
                    {
                        methodname: 'local_lytix_lytix_planner_event_completed',
                        args: {
                            contextid: planner.contextid,
                            jsonformdata: JSON.stringify(formData)
                        },
                    }
                ]);
                promises[0].then(function() {
                    if (!planner.includeTypes.includes(root.find('form')[0][7].value)) {
                        planner.includeTypes.push(root.find('form')[0][7].value);
                    }
                    planner.resetModal();
                    return;
                }).catch(function(ex) {
                    // TODO Find solution to show error message in modal.
                    Log.warn(ex);
                    planner.resetModal();
                });
            });
            root.on(ModalEvents.hidden, function() {
                delete item.userid;
                delete item.eventid;
                log('CLOSE', 'EVENT', null, item.id);
                modal.hide();
                modal.destroy();
            });
            root.on(ModalEvents.cancel, function() {
                planner.resetModal();
            });
            return;
        }).catch(function(ex) {
            Log.warn(ex);
        });
    },
    createCourseSettingsModal: function() {
        const form = loadFragment('lytix_planner', 'new_course_notification_settings_form',
            planner.contextid, null);

        const title = planner.strings.open_settings;
        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: title,
            body: form,
        }).then(function(modal) {
            // Forms are big, we want a big modal.
            modal.setLarge();
            modal.show();
            const root = modal.getRoot();
            root.on(ModalEvents.save, function(e) {
                // Convert all the form elements values to a serialised string.
                const formData = root.find('form').serialize();
                let MandatoryField = true;
                // Check mandatory fields.
                if (document.getElementById('id_new_type').checked === true) {
                    MandatoryField = false;
                    if (document.getElementById('id_select_other_german').value === '' ||
                        document.getElementById('id_select_other_english').value === '') {
                        e.preventDefault();
                        modal.setBody(modal.getBody().innerHTML);
                        // TODO remove, is done by the modal.
                        modal.getBody().append(planner.strings.type_required);
                    } else {
                        if (planner.includeTypes.includes(document.getElementById('id_select_other_german').value) ||
                            planner.includeTypes.includes(document.getElementById('id_select_other_english').value)) {
                            e.preventDefault();
                            modal.setBody(modal.getBody().innerHTML);
                            modal.getBody().append(planner.strings.type_exists);
                        } else {
                            MandatoryField = true;
                        }
                    }
                }
                planner.includeTypes.forEach(function(types) {
                    if (document.getElementById('id_delete' + types) &&
                        document.getElementById('id_delete' + types).checked === true) {
                        MandatoryField = false;
                        e.preventDefault();
                        const typenotdeleteable = getString('type_not_deleteable', 'lytix_planner');
                        typenotdeleteable.then(function(localizedString) {
                            modal.setBody(modal.getBody().innerHTML);
                            modal.getBody().append(localizedString);
                            return localizedString;
                        }).catch((error) => {
                            Log.warn(error);
                            throw error;
                        });
                    }
                });
                if (document.getElementById('id_softlock').checked &&
                    (document.getElementById('id_new_type').checked === false && MandatoryField) || MandatoryField) {
                    // Call the webservice with formData as param.
                    const promises = Ajax.call([
                        {
                            methodname: 'lytix_planner_store_course_notification_settings',
                            args: {
                                contextid: planner.contextid,
                                courseid: planner.courseid,
                                jsonformdata: JSON.stringify(formData)
                            },
                        }
                    ]);
                    promises[0].then(function(response) {
                        if (!response.success) {
                            Log.warn('Error storing custom course settings!');
                        }
                        planner.includedTypes = null;
                        planner.resetModal();
                        planner.update();
                        return;
                    }).catch(function(ex) {
                        Log.warn(ex);
                        planner.resetModal();
                    });
                }
            });
            root.on(ModalEvents.hidden, function() {
                log('CLOSE', 'SETTINGS');
                modal.hide();
                modal.destroy();
            });
            root.on(ModalEvents.cancel, function() {
                planner.resetModal();
            });
            return;
        }).catch(function(ex) {
            Log.warn(ex);
        });
    },

    addMilestoneBtn: function() {
        const item = {};
        item.id = -1;
        item.userid = planner.userid;
        item.marker = 'M';
        item.color = '#6495ED';

        const addMilestoneBtn = document.createElement('button');
        addMilestoneBtn.setAttribute('class', 'btn btn-primary');
        addMilestoneBtn.setAttribute('type', 'button');
        addMilestoneBtn.setAttribute('value', 'Add Milestone');
        addMilestoneBtn.setAttribute('data-action', 'Add Milestone');
        addMilestoneBtn.setAttribute('style', 'display: inline-block; margin: 4px 4px 4px 12px; float: right;');
        const textPresent = planner.strings.add_milestone;
        addMilestoneBtn.appendChild(document.createTextNode(textPresent));
        addMilestoneBtn.addEventListener('click', () => {
            log('OPEN', 'MILESTONE', null, item.id);
            planner.createMilestoneModal(item);
        });
        return addMilestoneBtn;
    },

    addEventBtn: function() {
        const item = {};
        item.id = -1;
        item.userid = planner.userid;
        item.marker = 'L';
        item.color = '#6495ED';
        item.visible = 1;
        const addEventBtn = document.createElement('button');
        addEventBtn.setAttribute('class', 'btn btn-primary');
        addEventBtn.setAttribute('type', 'button');
        addEventBtn.setAttribute('value', 'Add Event');
        addEventBtn.setAttribute('data-action', 'Add Event');
        addEventBtn.setAttribute('style', 'display: inline-block; margin: 4px 4px 4px 12px; float: right;');
        const textPresent = planner.strings.add_event;
        addEventBtn.appendChild(document.createTextNode(textPresent));
        addEventBtn.addEventListener('click', () => {
            log('OPEN', 'EVENT', null, item.id);
        });
        addEventBtn.addEventListener('click', () => {
            planner.createEventModal(item);
        });
        return addEventBtn;
    },

    // addNotificationUserSettingsBtn: function() {
    //     const userSettingsBtn = document.createElement('button');
    //     userSettingsBtn.setAttribute('class', 'btn btn-primary');
    //     userSettingsBtn.setAttribute('type', 'button');
    //     userSettingsBtn.setAttribute('value', 'notification_user_settings');
    //     userSettingsBtn.setAttribute('data-action', 'notification_user_settings');
    //     userSettingsBtn.setAttribute('style', 'display: inline-block; margin: 4px 4px 4px 12px; float: right;');
    //     const textPresent = planner.strings.open_settings;
    //     userSettingsBtn.appendChild(document.createTextNode(textPresent));
    //     userSettingsBtn.addEventListener('click', () => {
    //         log('OPEN', 'SETTINGS');
    //         planner.createUserSettingsModal();
    //     });
    //     return userSettingsBtn;
    // },

    addNotificationCourseSettingsBtn: function() {
        const userSettingsBtn = document.createElement('button');
        userSettingsBtn.setAttribute('class', 'btn btn-primary');
        userSettingsBtn.setAttribute('type', 'button');
        userSettingsBtn.setAttribute('value', 'notification_course_settings');
        userSettingsBtn.setAttribute('data-action', 'notification_course_settings');
        userSettingsBtn.setAttribute('style', 'display: inline-block; margin: 4px 4px 4px 12px; float: right;');
        const textPresent = planner.strings.open_settings;
        userSettingsBtn.appendChild(document.createTextNode(textPresent));
        userSettingsBtn.addEventListener('click', () => {
            log('OPEN', 'SETTINGS');
            planner.createCourseSettingsModal();
        });
        return userSettingsBtn;
    },

    addViewSelectionDrpdwn: function() {
        const viewSelectionsDrdwn = document.createElement('div');
        viewSelectionsDrdwn.setAttribute('class', 'planner-viewDropdown');
        const drdwnBtn = document.createElement('button');
        drdwnBtn.setAttribute('class', 'btn btn-secondary dropdown-toggle');
        drdwnBtn.setAttribute('type', 'button');
        drdwnBtn.setAttribute('id', 'dropdownMenuButton');
        drdwnBtn.setAttribute('data-toggle', 'dropdown');
        drdwnBtn.setAttribute('aria-haspopup', 'true');
        drdwnBtn.setAttribute('aria-expanded', 'false');
        drdwnBtn.setAttribute('style', 'display: inline-block; margin: 4px 4px 4px 12px; float: right;');

        drdwnBtn.innerText = planner.strings.view + ': ' + planner.view;
        viewSelectionsDrdwn.appendChild(drdwnBtn);

        const menu = document.createElement('div');
        menu.setAttribute('class', 'dropdown-menu');
        menu.setAttribute('aria-labelledby', 'dropdownMenuButton');

        const view1 = document.createElement('a');
        view1.setAttribute('class', 'dropdown-item');
        view1.setAttribute('href', '#');
        view1.innerText = planner.strings.month;
        view1.addEventListener('click', () => {
            planner.view = view1.innerText;
            drdwnBtn.innerText = planner.view;
            planner.update();
            // Here we also need to trigger the event of changing the layout of planner
        });

        const view3 = document.createElement('a');
        view3.setAttribute('class', 'dropdown-item');
        view3.setAttribute('href', '#');
        view3.innerText = '3 ' + planner.strings.months;
        view3.addEventListener('click', () => {
            planner.view = view3.innerText;
            drdwnBtn.innerText = planner.view;
            planner.update();
            // Here we also need to trigger the event of changing the layout of planner
        });
        const viewAll = document.createElement('a');
        viewAll.setAttribute('class', 'dropdown-item');
        viewAll.setAttribute('href', '#');
        viewAll.innerText = planner.locale.startsWith('de') ? 'gesamtes Semester' : 'entire semester';
        viewAll.addEventListener('click', () => {
            planner.view = viewAll.innerText;
            drdwnBtn.innerText = planner.view;
            planner.update();
            // Here we also need to trigger the event of changing the layout of planner
        });
        // Append the 3 view options
        menu.appendChild(view1);
        menu.appendChild(view3);
        menu.appendChild(viewAll);

        viewSelectionsDrdwn.appendChild(menu);

        return viewSelectionsDrdwn;
    },

    createMilestoneModal: function(item) {
        const form = loadFragment('lytix_planner', 'new_milestone_form', planner.contextid, item);
        const title = planner.strings.Milestone;
        let m;
        if (Number(M.cfg.version) < 2024042200) {
            m = ModalFactory.create({
                type: ModalType,
                title: title,
                body: form,
            });
        } else {
            m = ModalType.create({
                title: title,
                body: form,
            });
        }
        m.then(function(modal) {
            // Forms are big, we want a big modal.
            modal.setLarge();
            modal.show();
            const root = modal.getRoot();
            root.on(ModalEvents.save, function(e) {
                // Convert all the form elements values to a serialised string.
                const formData = root.find('form').serialize();
                let MandatoryFlag = true;
                if (document.getElementById('id_title').value === '') {
                    MandatoryFlag = false;
                    e.preventDefault();
                }
                const milestoneDate = new Date(document.getElementById('id_startdate_year').value,
                    document.getElementById('id_startdate_month').value - 1,
                    document.getElementById('id_startdate_day').value);
                const timestampofmilestone = milestoneDate.getTime();
                if (timestampofmilestone < planner.startDate || timestampofmilestone > planner.endDate) {
                    MandatoryFlag = false;
                    e.preventDefault();
                    const timeoutofrange = getString('timeoutofrange', 'lytix_planner');
                    timeoutofrange.then(function(localizedString) {
                        modal.setBody(modal.getBody().innerHTML);
                        modal.getBody().append(localizedString);
                        return localizedString;
                    }).catch((error) => {
                        Log.warn(error);
                        throw error;
                    });
                }
                const starthour = parseInt(document.getElementById('id_startdate_hour').value);
                const endhour = parseInt(document.getElementById('id_hour').value);
                const endminute = parseInt(document.getElementById('id_minute').value);
                const startminute = parseInt(document.getElementById('id_startdate_minute').value);
                if ((endhour < starthour) || (starthour === endhour && endminute < startminute)) {
                    MandatoryFlag = false;
                    e.preventDefault();
                    const timesmaller = getString('time_smaller', 'lytix_planner');
                    timesmaller.then(function(localizedString) {
                        modal.setBody(modal.getBody().innerHTML);
                        modal.getBody().append(localizedString);
                        return localizedString;
                    }).catch((error) => {
                        Log.warn(error);
                        throw error;
                    });
                }
                if (MandatoryFlag) {
                    // Call the webservice with formData as param.
                    const promises = Ajax.call([
                        {
                            methodname: 'local_lytix_lytix_planner_milestone',
                            args: {
                                contextid: planner.contextid,
                                jsonformdata: JSON.stringify(formData)
                            },
                        }
                    ]);
                    promises[0].then(function() {
                        if (!planner.includedTypes.includes('Milestone')) {
                            planner.includedTypes.push('Milestone');
                        }
                        planner.resetModal();
                        return;
                    }).catch(function(ex) {
                        // TODO Find solution to show error message in modal.
                        Log.warn(ex);
                        planner.resetModal();
                    });
                }
            });
            root.on('modal-save-delete-cancel:delete', function() {
                const promises = Ajax.call([
                    {
                        methodname: 'local_lytix_lytix_planner_delete_milestone',
                        args: {
                            contextid: planner.contextid,
                            courseid: planner.courseid,
                            userid: planner.userid,
                            id: item.id
                        },
                    }
                ]);
                promises[0].then(response => {
                    if (response.success) {
                        planner.resetModal();
                        planner.update();
                    }
                    return;
                }).catch(function(ex) {
                    Log.warn(ex);
                    planner.resetModal();
                });
            });
            root.on(ModalEvents.hidden, function() {
                log('CLOSE', 'MILESTONE', null, item.id);
                modal.hide();
                modal.destroy();
            });
            root.on(ModalEvents.cancel, function() {
                planner.resetModal();
            });
            return;
        }).catch(function(ex) {
            Log.warn(ex);
        });
    },

    renderModalFail: function(ex, id) {
        document.querySelector('#' + id + '.modal-body').innerHTML
            = planner.strings.error_text
            + '<p>'
            + ex.message
            + '</p>';
    },

    update: function() {
        const urlParams = new URLSearchParams(window.location.search);
        let id = 0;
        if (urlParams.has('id')) {
            id = urlParams.get('id');
        }
        Ajax.call([{
            methodname: 'local_lytix_lytix_planner_get',
            // XXX ‘isstudent’ in WS call is misleading, we had to reverse logic and check for teacher role instead.
            // See issue: https://gitlab.tugraz.at/llt/moodledev/plugindev/lytix/-/issues/73
            args: {id: id, contextid: planner.contextid, isstudent: !planner.isteacher}
        }])[0]
            .then(function(response) {
                planner.init(response);
                planner.storeEvents(response.items);
                planner.drawLegend(planner.data.items);
                planner.createMenu();
                planner.drawMarker(planner.data.items);
                planner.drawplanner();
                return;
            })
            .catch(function(ex) {
                document.getElementById('planner_menu').innerHTML
                    = planner.strings.error_text
                    + '<p>'
                    + ex.message
                    + '</p>';
            });
    },

    // Finding way around using native JS to calculate week numbers
    // Source: https://weeknumber.com/how-to/javascript
    // Returns the ISO week of the date.
    getWeek: function(curDate) {
        const date = new Date(curDate.getTime());
        date.setHours(0, 0, 0, 0);
        // Thursday in current week decides the year.
        date.setDate(date.getDate() + 3 - (date.getDay() + 6) % 7);
        // January 4 is always in week 1.
        const week1 = new Date(date.getFullYear(), 0, 4);
        // Adjust to Thursday in week 1 and count number of weeks from date to week1.
        return 1 + Math.round(((date.getTime() - week1.getTime()) / 86400000
            - 3 + (week1.getDay() + 6) % 7) / 7);
    },
};

export const init = async(contextid, courseid, userid, isteacher, locale) => {
    planner.isteacher = isteacher;
    locale = Widget.convertLocale(locale);

    planner.strings = await getStrings({
        lytix_planner: { // eslint-disable-line camelcase
            identical: [
                'add_milestone',
                'add_event',
                'Milestone',
                'event',
                'event_completed',
                'error_text',
                'loading_msg',
                'open_settings',
                'type_exists',
                'type_required',
                'costum_settings',
                'event_limit',
                'legend',
                'view',
                'month',
                'months',
                'next',
                'previous_month',
                'options',
                'calendarweek',
                'completed_by',
                'students',
                'description_teacher',
                'description_student',
                'Milestone',
                'Lecture',
                'Exam',
                'Assignment',
                'Feedback',
                'Interview',
                'Other',
                'Quiz'
           ],
        },
    });

    // The following block is a consequence of questionable design decisions in the backend:
    // In the database event types are stored as their English and German name joined by an underscore.
    // Instead of a proper identifier, we get either the German or the English part, depending on the current language.
    // Because we use custom language strings to label the event type, not the string provided by the webservice,
    // we have to map the language strings to the either German or English event type name.
    {
        const
            strings = planner.strings,
            // The following arrays are connected by index: For example, index 2 in each array is the translation of the other.
            stringIdentifiers = ['Assignment', 'Exam', 'Feedback', 'Interview', 'Lecture', 'Milestone', 'Quiz'],
            eventIdentifiers = locale.slice(0, 2) == 'de'
                ? ['Aufgabe', 'Prüfung', 'Feedback', 'Abgabegespräch', 'Vorlesung', 'Meilenstein', 'Quiz']
                : stringIdentifiers;
        for (let i = stringIdentifiers.length - 1; i >= 0; --i) {
            eventStrings[eventIdentifiers[i]] = strings[stringIdentifiers[i]];
        }
    }

    planner.drawLoading();
    planner.contextid = contextid;
    planner.courseid = courseid;
    planner.userid = userid;
    planner.locale = locale;
    planner.dateFormatter = new Intl.DateTimeFormat(planner.locale, {
        day: 'numeric', month: 'numeric', year: '2-digit',
    });
    planner.timeFormatter = new Intl.DateTimeFormat(planner.locale, {
        hour: '2-digit', minute: '2-digit',
    });
    planner.update();

    log = makeLoggingFunction(userid, courseid, contextid, 'planner');

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            planner.update();
        }, 250);
    });
};
