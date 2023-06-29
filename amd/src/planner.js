import $ from 'jquery';
import Ajax from 'core/ajax';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import {loadFragment} from 'core/fragment';
import Yui from 'core/yui';
import {get_string as getString} from 'core/str';
import {makeLoggingFunction} from 'lytix_logs/logs';
import ModalType from 'lytix_planner/modal_save_delete_cancel';
import {getStrings} from 'lytix_helper/widget';

const d3 = window.d3;
var moment = window.moment;

var svgcontainer = document.getElementById('PlannerWidget');
var svgElement = null;

var svgHeight = 250;
var svgWidth = 600;

let log; // Will be the logging function.

var planner = {

    contextid: -1,
    courseid: -1,
    userid: -1,
    isteacher: -1,
    strings: null,

    isresizing: false,

    paddingleft: 20,
    paddingright: 60,
    padding: 4,
    barHeight: 20,
    days: 0,
    months: 0,
    startDate: null,
    endDate: null,
    daysWidth: 0,
    monthWidth: 0,
    data: null,
    showMonthFlag: false,

    includeTypes: null,
    includedTypes: null,

    storedEvents: new Map(),
    storedMilestones: new Map(),

    dateFormatter: new Intl.DateTimeFormat('de-AT', {
        day: 'numeric', month: 'numeric', year: '2-digit',
    }),
    timeFormatter: new Intl.DateTimeFormat('de-AT', {
        hour: '2-digit', minute: '2-digit',
    }),

    init: function(data) {
        if (svgcontainer !== null) {
            svgWidth = svgcontainer.offsetWidth;
        }

        var widget = document.getElementById('PlannerWidget');
        widget.innerHTML = '';

        var PlannerWidget = d3.select("#PlannerWidget");
        PlannerWidget.selectAll('*').remove();
        svgElement = PlannerWidget.append("svg").attr("width", svgWidth).attr("height", svgHeight);

        planner.data = data;
        if (planner.showMonthFlag === false) {
            planner.startDate = moment.unix(data.startDate);
            var date = new Date(data.endDate * 1000);
            date.setHours(23);
            date.setMinutes(59);
            date = date.getTime();
            planner.endDate = moment.unix(date / 1000);

            planner.days = planner.endDate.diff(planner.startDate, 'days');
            planner.months = planner.endDate.diff(planner.startDate, 'months') + 1;
            planner.dayWidth = (svgWidth - (planner.padding * (planner.months - 1)) -
                (planner.paddingleft + planner.paddingright)) / planner.days;
            planner.monthWidth = (svgWidth - (planner.padding * (planner.months - 1)) -
                (planner.paddingleft + planner.paddingright)) / planner.months;
        }
    },

    getType: function(a) {
        return a.type;
    },

    storeEvents: function(events) {
        planner.storedEvents = new Map();
        planner.storedMilestones = new Map();
        for (let i = 0; i < events.length; i++) {
            var date = new Date(events[i].startdate * 1000);
            date.setHours(0);
            date.setMinutes(0);
            date = date.getTime();
            if (events[i].type === "Milestone" || events[i].type === "Meilenstein") {
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
        var img = '<img src="../../../pix/i/loading.gif" ' +
            'alt="LoadingImage" style="width:48px;height:48px;">';
        var widget = document.getElementById('PlannerWidget');
        widget.innerHTML = img + ' ' + planner.strings.loading_msg;
    },

    updateSvgHeight: function(height) {
        document.querySelector('#PlannerWidget svg').setAttribute('height', height);
    },

    drawplanner: function() {
        const y = planner.barPosY;
        var date = planner.startDate.clone();
        const onClick = function() {
            const type = planner.showMonthFlag ? 'CLOSE' : 'OPEN';
            log(type, 'MONTH');
            const attr = this.hasAttribute('x') ? 'x' : 'x1';
            planner.showMonth(d3.select(this).attr(attr));
        };
        for (var i = 0; i < planner.months; ++i) {
            svgElement.append("line")
                .attr("x1", planner.paddingleft + i * planner.monthWidth + i * planner.padding)
                .attr("y1", y)
                .attr("x2", planner.paddingleft + (i + 1) * planner.monthWidth + i * planner.padding)
                .attr("y2", y)
                .attr("cursor", "pointer")
                .style("stroke", "#000")
                .style("stroke-width", planner.barHeight)
                .on("click", onClick);

            svgElement.append("text")
                .attr("x", planner.paddingleft + i * planner.padding + i * planner.monthWidth + planner.monthWidth / 2)
                .attr("y", y)
                .attr("dy", "6px")
                .attr("cursor", "pointer")
                .style("text-anchor", "middle")
                .style("font-weight", "normal")
                .style("font-family", "sans-serif")
                .attr("fill", "white")
                .text(date.format("MMM YYYY"))
                .on("click", onClick);
            date.add(1, "months");
        }
    },

    drawMarker: function(items) {
        items = items.filter(function(item) {
            return planner.includedTypes.indexOf(item.type) !== -1
                && (item.visible || planner.isteacher)
                && (item.startdate >= planner.startDate.unix() && item.startdate <= planner.endDate.unix());
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

        const
            count = items.length,
            markerPositions = new Array(count);

        for (let i = 0; i < count; ++i) {
            const
                item = items[i],
                itemDate = moment.unix(item.startdate),
                monthOffset = itemDate.diff(planner.startDate, 'months'),
                startDate = moment(itemDate).startOf('month'),
                endDate = moment(itemDate).endOf('month'),
                xPos = planner.paddingleft + monthOffset * planner.monthWidth
                    + monthOffset * planner.padding + 2
                    + ((planner.monthWidth - 4) / endDate.diff(startDate, 'days')) * itemDate.diff(startDate, 'days');
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
            };
        }

        const
            circleRadius = 15, // The radius of the marker, excluding stroke width.
            markerDiameter = circleRadius * 2,
            tooltipHeight = markerDiameter,
            needleLength = 40, // The length of the stroke connecting a marker with the date strip.
            eventsStartY =
                tooltipHeight
                + needleLength - circleRadius
                + stackTracker.max.events * markerDiameter,
            milestonesStartY = eventsStartY + planner.barHeight;

        planner.barPosY = eventsStartY + planner.barHeight / 2;

        // Change SVG height according to the maximum number of events on the same day.
        // Exclude milestones if there are none.
        svgHeight = stackTracker.max.milestones === 0
            ? milestonesStartY + circleRadius // Add some margin.
            : milestonesStartY
            + needleLength - circleRadius
            + stackTracker.max.milestones * markerDiameter
            + tooltipHeight;
        planner.updateSvgHeight(svgHeight);

        const startDate = new Date();
        const endDate = new Date();
        let index = 0;
        // Stick with forEach() because it would cause to many grunt errors when changed to a loop :-| .
        items.forEach(function(item) {
            const i = index++;

            startDate.setTime(item.startdate * 1000);
            endDate.setTime(item.enddate * 1000);

            const
                xPos = markerPositions[i].x,
                stackPos = markerPositions[i].stackPos;
            let needleStart, needleEnd, yPos, tooltipY;
            if (item.userid) { // Check if milestone.
                needleStart = milestonesStartY;
                needleEnd = needleStart + needleLength;
                yPos = needleEnd + markerDiameter * (stackPos - 1);
                tooltipY = needleEnd + (stackTracker.max.milestones - 1) * markerDiameter + tooltipHeight;
            } else {
                needleStart = eventsStartY;
                needleEnd = needleStart - needleLength;
                yPos = needleEnd - markerDiameter * (stackPos - 1);
                tooltipY = needleEnd - (stackTracker.max.events - 1) * markerDiameter - tooltipHeight;
            }

            var anchor = "middle";
            if (xPos < planner.paddingleft + 254.4) {
                anchor = "start";
            } else if (xPos > planner.paddingleft + 254.4 * 4) {
                anchor = "end";
            }

            var tooltip = item.title + ' • '
                + planner.dateFormatter.format(startDate) + ', '
                + planner.timeFormatter.format(startDate) + ' – '
                + planner.timeFormatter.format(endDate) + ' • '
                + "completed by " + item.countcompleted + " student(s)";

            var hover = svgElement.append("text")
                .attr("class", "hover")
                .attr("x", xPos)
                .attr("y", tooltipY)
                .attr("dy", "6px")
                .style("text-anchor", anchor)
                .style("font-size", "14px")
                .style("font-weight", "simple")
                .style("font-family", "sans-serif")
                .style("display", "none")
                .attr("fill", "black")
                .text(tooltip);

            const
                fill = planner.getColor(item),
                stroke = item.mandatory ? "#474747" : fill;

            if (stackPos <= 1) {
                svgElement.append("line")
                    .attr("x1", xPos)
                    .attr("y1", needleStart)
                    .attr("x2", xPos)
                    .attr("y2", needleEnd)
                    .style("stroke", stroke)
                    .style("stroke-width", 4)
                    .attr("cursor", "pointer")
                    .on("click", function() {
                        planner.showModal(item);
                    })
                    .on("mouseover", function() {
                        hover.style("display", null);
                    })
                    .on("mouseout", function() {
                        hover.style("display", "none");
                    });
            }

            svgElement.append("circle")
                .attr("cx", xPos)
                .attr("cy", yPos)
                .attr("r", circleRadius)
                .attr("fill", fill)
                .style("stroke", stroke)
                .style("stroke-width", 3.5)
                .attr("cursor", "pointer")
                .on("click", function() {
                    planner.showModal(item);
                })
                .on("mouseover", function() {
                    hover.style("display", null);
                })
                .on("mouseout", function() {
                    hover.style("display", "none");
                });

            const label = item.graded ? item.type[0] + "*" : item.type[0];
            svgElement.append("text")
                .attr("x", xPos)
                .attr("y", yPos)
                .attr("dy", "6px")
                .style("text-anchor", "middle")
                .style("font-weight", "bold")
                .style("font-family", "sans-serif")
                .attr("fill", "#fefefe")
                .text(label)
                .attr("cursor", "pointer")
                .on("click", function() {
                    planner.showModal(item);
                })
                .on("mouseover", function() {
                    hover.style("display", null);
                })
                .on("mouseout", function() {
                    hover.style("display", "none");
                });
        });
    },

    // Takes event data (as fetched from backend), and returns a hex code.
    getColor: (() => {
        const
            now = Date.now() / 1000,
            threshold = now + 604800; // Seven days in seconds.
        return event => {
            const end = event.enddate;
            return (event.completed && "#b2c204") // Green.
                || (end < now && (planner.isteacher && "#a09e9e" || "#df3540")) // Student → red; teacher → grey.
                || (end <= threshold && "#f9a606") // Yellow/Orange
                || "#a09e9e"; // Grey.
        };
    })(),

    drawLegend: function(items) {
        var legendcontainer = document.getElementById('planner_legend');
        var svgWidth = 600;
        var svgHeight = 100;
        if (svgcontainer !== null) {
            svgWidth = svgcontainer.offsetWidth;
        }
        d3.select("#planner_legend").attr("width", svgWidth).attr("height", svgHeight);

        var start = planner.startDate.format("YYYY-MM-DD");
        var end = planner.endDate.format("YYYY-MM-DD");

        var legend = {};
        var marker = {};

        items.forEach(function(item) {
            if (moment.unix(item.startdate) <= planner.endDate && (planner.isteacher || item.visible)) {
                if (!(item.type in legend)) {
                    legend[item.type] = 0;
                }
                var TmpDate = moment.unix(item.startdate)._d;
                var year = String(TmpDate.getFullYear());
                var month = String((TmpDate.getMonth() + 1));
                var day = String(TmpDate.getDate());
                if (month.length < 2) {
                    month = '0' + month;
                }
                if (day.length < 2) {
                    day = '0' + day;
                }
                var x = [year, month, day].join('-');
                if (x < start || x > end) {
                    return;
                }

                legend[item.type] = legend[item.type] + 1;
                marker[item.type] = item.marker;
            }
        });

        var filter = [];

        var innerHTML = "";
        for (var prop in legend) {
            if (legend[prop] !== 0) {
                filter.push(prop);
                if (Object.prototype.hasOwnProperty.call(legend, prop)) {
                    innerHTML += '<div class="d-inline-block" style="padding: 5px 10px; font-size: 16px;">'
                        + '<div class="d-inline-block font-weight-bold text-center" style="width: 36px; height: 36px;'
                        + 'line-height: 32px; border-radius: 50%; border: 2px solid #444444; color: #000;'
                        + 'background: #babdbf; margin-right: 6px;">'
                        + prop[0] + '</div>' + legend[prop] + ' ' + prop + '</div>';
                }
            }
        }
        innerHTML += "<p class='ml-3 mb-0' style='font-size: 0.85em;'>" + planner.strings.legend + "</p>";
        planner.includeTypes = filter;
        if (planner.includedTypes === null) {
            planner.includedTypes = planner.includeTypes;
        }
        legendcontainer.innerHTML = innerHTML;
    },

    showMonth: function(xPos) {
        const PlannerWidget = d3.select("#PlannerWidget");
        PlannerWidget.selectAll('circle').remove();
        PlannerWidget.selectAll('line').remove();
        PlannerWidget.selectAll('text').remove();

        if (planner.months !== 1) {
            planner.showMonthFlag = true;

            const monthnumber = (xPos - planner.paddingleft) / (planner.monthWidth + planner.padding);
            planner.startDate.add(monthnumber, "months");
            if (planner.endDate > moment(planner.startDate).endOf('month')) {
                planner.endDate = moment(planner.startDate).endOf('month');
            }
            planner.months = 1;
            planner.monthWidth = svgWidth - (planner.paddingleft + planner.paddingright);

            planner.drawLegend(planner.data.items);
            planner.createMenu();
            planner.drawMarker(planner.data.items);
            planner.drawplanner();
        } else {
            planner.showMonthFlag = false;
            planner.update();
        }
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
        var menucontainer = document.getElementById('planner_menu');
        var svgWidth = 600;
        var svgHeight = 100;
        if (menucontainer !== null) {
            svgWidth = menucontainer.offsetWidth;
        }
        d3.select("#planner_menu").attr("width", svgWidth).attr("height", svgHeight);
        menucontainer.innerHTML = '';

        var form = document.createElement("form");

        form.setAttribute("style", "font-size: 16px; font-weight: normal; font-famiy: sans-serif");
        planner.includeTypes.forEach(function(item) {
            var input = document.createElement("input");
            input.setAttribute("type", "checkbox");
            input.setAttribute("value", item);
            input.setAttribute("style", "margin: 8px 4px 8px 20px;");
            if (planner.includedTypes.includes(item)) {
                input.checked = true;
            }
            input.addEventListener("change", function() {
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

            form.appendChild(input);
            form.appendChild(document.createTextNode(item));
        });

        if (!planner.isteacher) {
            var promises = Ajax.call([
                {
                    methodname: 'lytix_planner_allow_personalized_notifications',
                    args: {contextid: planner.contextid, courseid: planner.courseid},
                }
            ]);
            promises[0].done(function(response) {
                if (response.allow) {
                    form.appendChild(planner.addNotificationUserSettingsBtn());
                }
            }).fail(function(ex) {
                window.console.log(ex);
            });
            form.appendChild(planner.addMilestoneBtn());
        } else {
            form.appendChild(planner.addNotificationCourseSettingsBtn());
            form.appendChild(planner.addEventBtn());
        }
        menucontainer.appendChild(form);
    },

    resetModal: function() {
        Yui.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });
        // Reload that changes in view are done and a new fresh modal is created.
        planner.update();
    },

    checkDateandTime: function(MandatoryFlag, e, modal) {
        var startdate;
        var enddate;
        for (var i = 0; i <= 5; i++) {
            if (i === 0) {
                startdate = 'startdate';
                enddate = 'enddate';
            } else {
                startdate = 'date' + i;
                enddate = 'endtime' + i;
            }
            let eventDate = new Date(document.getElementById('id_' + startdate + '_year').value,
                document.getElementById('id_' + startdate + '_month').value - 1,
                document.getElementById('id_' + startdate + '_day').value);
            var timestampofevent = eventDate.getTime();
            if ((timestampofevent / 1000) < planner.startDate.unix() || (timestampofevent / 1000) > planner.endDate.unix()) {
                MandatoryFlag = false;
                e.preventDefault();
                var timeoutofrange = getString('timeoutofrange', 'lytix_planner');
                $.when(timeoutofrange).done(function(localizedString) {
                    modal.setBody(modal.getBody().innerHTML);
                    modal.getBody().append(localizedString);
                });
            }
            var starthour = parseInt(document.getElementById('id_' + startdate + '_hour').value);
            var endhour = parseInt(document.getElementById('id_' + enddate + 'hour').value);
            var endminute = parseInt(document.getElementById('id_' + enddate + 'minute').value);
            var startminute = parseInt(document.getElementById('id_' + startdate + '_minute').value);
            if ((endhour < starthour) || (starthour === endhour && endminute < startminute)) {
                MandatoryFlag = false;
                e.preventDefault();
                var timesmaller = getString('time_smaller', 'lytix_planner');
                $.when(timesmaller).done(function(localizedString) {
                    modal.setBody(modal.getBody().innerHTML);
                    modal.getBody().append(localizedString);
                });
            }
            if (document.getElementById('id_moreevents').value == 1) {
                break;
            }
        }
        return MandatoryFlag;
    },

    createEventModal: function(item) {
        var trigger = $('#create-modal');
        var form = loadFragment('lytix_planner', 'new_event_form', planner.contextid, item);
        var title = planner.strings.event;
        ModalFactory.create({
            type: ModalType,
            title: title,
            body: form,
        }, trigger).done(function(modal) {
            // Forms are big, we want a big modal.
            modal.setLarge();
            modal.show();
            var root = modal.getRoot();
            root.on(ModalEvents.save, function(e) {
                // Convert all the form elements values to a serialised string.
                var formData = root.find('form').serialize();
                var MandatoryFlag = true;
                if (document.getElementById('id_title').value === "") {
                    MandatoryFlag = false;
                    e.preventDefault();
                }
                MandatoryFlag = planner.checkDateandTime(MandatoryFlag, e, modal);
                // Beasts.indexOf('bison').
                let selectedOption =
                    document.getElementById('id_type').options[document.getElementById('id_type').selectedIndex];
                // Check mandatory fields.
                if (selectedOption.text === "Other") {
                    MandatoryFlag = false;
                    if (document.getElementById('id_select_other_german').value === "" ||
                        document.getElementById('id_select_other_english').value === "") {
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
                    var promises = Ajax.call([
                        {
                            methodname: 'local_lytix_lytix_planner_event',
                            args: {
                                contextid: planner.contextid,
                                jsonformdata: JSON.stringify(formData)
                            },
                        }
                    ]);
                    promises[0].done(function() {
                        if (!planner.includedTypes.includes(selectedOption.text)) {
                            planner.includedTypes.push(selectedOption.text);
                            planner.includedTypes = null;
                        }
                        planner.resetModal();
                        planner.update();
                    }).fail(function(ex) {
                        // TODO Find solution to show error message in modal.
                        window.console.log(ex);
                        planner.resetModal();
                    });
                }
            });
            root.on('modal-save-delete-cancel:delete', function() {
                var promises = Ajax.call([
                {
                    methodname: 'local_lytix_lytix_planner_delete_event',
                    args: {
                        contextid: planner.contextid, courseid: planner.courseid, userid: planner.userid, id: item.id},
                }
                ]);
                promises[0].done(function(response) {
                    if (response.success) {
                        planner.resetModal();
                        planner.update();
                    }
                }).fail(function(ex) {
                    window.console.log(ex);
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
        });
    },

    createEventCompletedModal: function(item) {
        var trigger = $('#create-modal');
        // Set correct userid.
        item.userid = planner.userid;
        item.eventid = item.id;
        var form = loadFragment('lytix_planner', 'new_event_completed_form', planner.contextid, item);

        var title = planner.strings.event_completed;
        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: title,
            body: form,
        }, trigger).done(function(modal) {
            // Forms are big, we want a big modal.
            modal.setLarge();
            modal.show();
            var root = modal.getRoot();
            root.on(ModalEvents.save, function() {
                // Convert all the form elements values to a serialised string.
                var formData = root.find('form').serialize();
                // Call the webservice with formData as param.
                var promises = Ajax.call([
                    {
                        methodname: 'local_lytix_lytix_planner_event_completed',
                        args: {
                            contextid: planner.contextid,
                            jsonformdata: JSON.stringify(formData)
                        },
                    }
                ]);
                promises[0].done(function() {
                    if (!planner.includeTypes.includes(root.find('form')[0][7].value)) {
                        planner.includeTypes.push(root.find('form')[0][7].value);
                    }
                    planner.resetModal();
                }).fail(function(ex) {
                    // TODO Find solution to show error message in modal.
                    window.console.log(ex);
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
        });
    },

    createUserSettingsModal: function() {
        var trigger = $('#create-modal');
        // Set correct userid.
        var params = {};
        params.userid = planner.userid;
        params.courseid = planner.courseid;
        var form = loadFragment('lytix_planner', 'new_user_notification_settings_form',
            planner.contextid, params);

        var title = planner.strings.open_settings;
        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: title,
            body: form,
        }, trigger).done(function(modal) {
            // Forms are big, we want a big modal.
            modal.setLarge();
            modal.show();
            var root = modal.getRoot();
            root.on(ModalEvents.save, function() {
                // Convert all the form elements values to a serialised string.
                var formData = root.find('form').serialize();
                // Call the webservice with formData as param.
                var promises = Ajax.call([
                    {
                        methodname: 'lytix_planner_store_user_notification_settings',
                        args: {
                            contextid: planner.contextid,
                            courseid: planner.courseid,
                            userid: planner.userid,
                            jsonformdata: JSON.stringify(formData)
                        },
                    }
                ]);
                promises[0].done(function(response) {
                    if (!response.success) {
                        window.console.log("Error storing user notification settings!");
                    }
                    planner.resetModal();
                }).fail(function(ex) {
                    window.console.log(ex);
                    planner.resetModal();
                });
            });
            root.on(ModalEvents.hidden, function() {
                log('CLOSE', 'SETTINGS');
                modal.hide();
                modal.destroy();
            });
            root.on(ModalEvents.cancel, function() {
                planner.resetModal();
            });
        });
    },

    createCourseSettingsModal: function() {
        var trigger = $('#create-modal');
        var form = loadFragment('lytix_planner', 'new_course_notification_settings_form',
            planner.contextid, null);

        var title = planner.strings.open_settings;
        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: title,
            body: form,
        }, trigger).done(function(modal) {
            // Forms are big, we want a big modal.
            modal.setLarge();
            modal.show();
            var root = modal.getRoot();
            root.on(ModalEvents.save, function(e) {
                // Convert all the form elements values to a serialised string.
                var formData = root.find('form').serialize();
                var MandatoryField = true;
                // Check mandatory fields.
                if (document.getElementById('id_new_type').checked === true) {
                    MandatoryField = false;
                    if (document.getElementById('id_select_other_german').value === "" ||
                        document.getElementById('id_select_other_english').value === "") {
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
                        var typenotdeleteable = getString('type_not_deleteable', 'lytix_planner');
                        $.when(typenotdeleteable).done(function(localizedString) {
                            modal.setBody(modal.getBody().innerHTML);
                            modal.getBody().append(localizedString);
                        });
                    }
                });
                if (document.getElementById('id_softlock').checked &&
                    (document.getElementById('id_new_type').checked === false && MandatoryField) || MandatoryField) {
                    // Call the webservice with formData as param.
                    var promises = Ajax.call([
                        {
                            methodname: 'lytix_planner_store_course_notification_settings',
                            args: {
                                contextid: planner.contextid,
                                courseid: planner.courseid,
                                jsonformdata: JSON.stringify(formData)
                            },
                        }
                    ]);
                    promises[0].done(function(response) {
                        if (!response.success) {
                            window.console.log("Error storing custom course settings!");
                        }
                        planner.includedTypes = null;
                        planner.resetModal();
                        planner.update();
                    }).fail(function(ex) {
                        window.console.log(ex);
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
        });
    },

    addMilestoneBtn: function() {
        var item = {};
        item.id = -1;
        item.userid = planner.userid;
        item.marker = 'M';
        item.color = '#6495ED';

        var addMilestoneBtn = document.createElement("button");
        addMilestoneBtn.setAttribute("class", "btn btn-primary");
        addMilestoneBtn.setAttribute("type", "button");
        addMilestoneBtn.setAttribute("value", "Add Milestone");
        addMilestoneBtn.setAttribute("data-action", "Add Milestone");
        addMilestoneBtn.setAttribute("style", "display: inline-block; margin: 4px 4px 4px 24px; float: right;");
        var textPresent = planner.strings.add_milestone;
        $.when(textPresent).done(function(localizedString) {
            addMilestoneBtn.appendChild(document.createTextNode(localizedString));
        });
        addMilestoneBtn.addEventListener("click", function() {
            log('OPEN', 'MILESTONE', null, item.id);
            planner.createMilestoneModal(item);
        });
        return addMilestoneBtn;
    },

    addEventBtn: function() {
        var item = {};
        item.id = -1;
        item.userid = planner.userid;
        item.marker = 'L';
        item.color = '#6495ED';
        item.visible = 1;
        var addEventBtn = document.createElement("button");
        addEventBtn.setAttribute("class", "btn btn-primary");
        addEventBtn.setAttribute("type", "button");
        addEventBtn.setAttribute("value", "Add Event");
        addEventBtn.setAttribute("data-action", "Add Event");
        addEventBtn.setAttribute("style", "display: inline-block; margin: 4px 4px 4px 24px; float: right;");
        var textPresent = planner.strings.add_event;
        $.when(textPresent).done(function(localizedString) {
            addEventBtn.appendChild(document.createTextNode(localizedString));
        });
        addEventBtn.addEventListener("click", function() {
            log('OPEN', 'EVENT', null, item.id);
        });
        addEventBtn.addEventListener("click", function() {
            planner.createEventModal(item);
        });
        return addEventBtn;
    },

    addNotificationUserSettingsBtn: function() {
        var userSettingsBtn = document.createElement("button");
        userSettingsBtn.setAttribute("class", "btn btn-primary");
        userSettingsBtn.setAttribute("type", "button");
        userSettingsBtn.setAttribute("value", "notification_user_settings");
        userSettingsBtn.setAttribute("data-action", "notification_user_settings");
        userSettingsBtn.setAttribute("style", "display: inline-block; margin: 4px 4px 4px 24px; float: right;");
        var textPresent = planner.strings.open_settings;
        $.when(textPresent).done(function(localizedString) {
            userSettingsBtn.appendChild(document.createTextNode(localizedString));
        });
        userSettingsBtn.addEventListener("click", function() {
            log('OPEN', 'SETTINGS');
            planner.createUserSettingsModal();
        });
        return userSettingsBtn;
    },

    addNotificationCourseSettingsBtn: function() {
        var userSettingsBtn = document.createElement("button");
        userSettingsBtn.setAttribute("class", "btn btn-primary");
        userSettingsBtn.setAttribute("type", "button");
        userSettingsBtn.setAttribute("value", "notification_course_settings");
        userSettingsBtn.setAttribute("data-action", "notification_course_settings");
        userSettingsBtn.setAttribute("style", "display: inline-block; margin: 4px 4px 4px 24px; float: right;");
        var textPresent = planner.strings.open_settings;
        $.when(textPresent).done(function(localizedString) {
            userSettingsBtn.appendChild(document.createTextNode(localizedString));
        });
        userSettingsBtn.addEventListener("click", function() {
            log('OPEN', 'SETTINGS');
            planner.createCourseSettingsModal();
        });
        return userSettingsBtn;
    },

    createMilestoneModal: function(item) {
        var trigger = $('#create-modal');
        var form = loadFragment('lytix_planner', 'new_milestone_form', planner.contextid, item);
        var title = planner.strings.Milestone;
        ModalFactory.create({
            type: ModalType,
            title: title,
            body: form,
        }, trigger).done(function(modal) {
            // Forms are big, we want a big modal.
            modal.setLarge();
            modal.show();
            var root = modal.getRoot();
            root.on(ModalEvents.save, function(e) {
                // Convert all the form elements values to a serialised string.
                var formData = root.find('form').serialize();
                var MandatoryFlag = true;
                if (document.getElementById('id_title').value === "") {
                    MandatoryFlag = false;
                    e.preventDefault();
                }
                let milestoneDate = new Date(document.getElementById('id_startdate_year').value,
                    document.getElementById('id_startdate_month').value - 1,
                    document.getElementById('id_startdate_day').value);
                var timestampofmilestone = milestoneDate.getTime();
                if (timestampofmilestone < planner.startDate || timestampofmilestone > planner.endDate) {
                    MandatoryFlag = false;
                    e.preventDefault();
                    var timeoutofrange = getString('timeoutofrange', 'lytix_planner');
                    $.when(timeoutofrange).done(function(localizedString) {
                        modal.setBody(modal.getBody().innerHTML);
                        modal.getBody().append(localizedString);
                    });
                }
                var starthour = parseInt(document.getElementById('id_startdate_hour').value);
                var endhour = parseInt(document.getElementById('id_hour').value);
                var endminute = parseInt(document.getElementById('id_minute').value);
                var startminute = parseInt(document.getElementById('id_startdate_minute').value);
                if ((endhour < starthour) || (starthour === endhour && endminute < startminute)) {
                    MandatoryFlag = false;
                    e.preventDefault();
                    var timesmaller = getString('time_smaller', 'lytix_planner');
                    $.when(timesmaller).done(function(localizedString) {
                        modal.setBody(modal.getBody().innerHTML);
                        modal.getBody().append(localizedString);
                    });
                }
                if (MandatoryFlag) {
                    // Call the webservice with formData as param.
                    var promises = Ajax.call([
                        {
                            methodname: 'local_lytix_lytix_planner_milestone',
                            args: {
                                contextid: planner.contextid,
                                jsonformdata: JSON.stringify(formData)
                            },
                        }
                    ]);
                    promises[0].done(function() {
                        if (!planner.includedTypes.includes("Milestone")) {
                            planner.includedTypes.push("Milestone");
                        }
                        planner.resetModal();
                    }).fail(function(ex) {
                        // TODO Find solution to show error message in modal.
                        window.console.log(ex);
                        planner.resetModal();
                    });
                }
            });
            root.on('modal-save-delete-cancel:delete', function() {
                var promises = Ajax.call([
                    {
                        methodname: 'local_lytix_lytix_planner_delete_milestone',
                        args: {
                            contextid: planner.contextid, courseid: planner.courseid, userid: planner.userid, id: item.id},
                    }
                ]);
                promises[0].done(function(response) {
                    if (response.success) {
                        planner.resetModal();
                        planner.update();
                    }
                }).fail(function(ex) {
                    window.console.log(ex);
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
        });
    },

    renderModalFail: function(ex, id) {
        var text = planner.strings.error_text + '<p>' + ex.message + '</p>';
        $('#' + id + '.modal-body').html(text);
    },

    update: function() {
        var urlParams = new URLSearchParams(window.location.search);
        var id = 0;
        if (urlParams.has('id')) {
            id = urlParams.get('id');
        }
        Ajax.call([{
            methodname: 'local_lytix_lytix_planner_get',
            // XXX ‘isstudent’ in WS call is misleading, we had to reverse logic and check for teacher role instead.
            // See issue: https://gitlab.tugraz.at/llt/moodledev/plugindev/lytix/-/issues/73
            args: {id: id, contextid: planner.contextid, isstudent: !planner.isteacher}
        }])[0]
        .done(function(response) {
            planner.init(response);
            planner.storeEvents(response.items);
            planner.drawLegend(planner.data.items);
            planner.createMenu();
            planner.drawMarker(planner.data.items);
            planner.drawplanner();
        })
        .fail(function(ex) {
            var text = planner.strings.error_text + '<p>' + ex.message + '</p>';
            $('#planner_menu').html(text);
        });
    }
};

export const init = async(contextid, courseid, userid, isteacher) => {
    planner.isteacher = isteacher;

    planner.strings = await getStrings({
        lytix_planner: { // eslint-disable-line camelcase
            identical: [
                "add_milestone",
                "add_event",
                "Milestone",
                "event",
                "event_completed",
                "error_text",
                "loading_msg",
                "open_settings",
                "type_exists",
                "type_required",
                "costum_settings",
                "event_limit",
                "legend",
            ],
        },
    });

    planner.drawLoading();
    planner.contextid = contextid;
    planner.courseid = courseid;
    planner.userid = userid;
    planner.update();

    log = makeLoggingFunction(userid, courseid, contextid, 'planner');

    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            planner.update();
        }, 250);
    });
};
