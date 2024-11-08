import $ from 'jquery';
import Notification from 'core/notification';
import * as CustomEvents from 'core/custom_interaction_events';
import Modal from 'core/modal';
import ModalRegistry from 'core/modal_registry';

const SELECTORS = {
    SAVE_BUTTON: '[data-action="save"]',
    CANCEL_BUTTON: '[data-action="cancel"]',
    DELETE_BUTTON: '[data-action="delete"]',
};

let r;
if (Number(M.cfg.version) < 2024042200) {
    // Moodle < 4.4 .
    var registered = false;
    /**
     * Constructor for the Modal.
     *
     * @param {object} root The root jQuery element for the modal
     */
    var ModalType = function(root) {
        Modal.call(this, root);

        if (!this.getFooter().find(SELECTORS.SAVE_BUTTON).length) {
            Notification.exception({message: 'No save button found'});
        }

        if (!this.getFooter().find(SELECTORS.DELETE_BUTTON).length) {
            Notification.exception({message: 'No delete button found'});
        }

        if (!this.getFooter().find(SELECTORS.CANCEL_BUTTON).length) {
            Notification.exception({message: 'No cancel button found'});
        }
    };
    ModalType.TYPE = 'lytix_planner-modal_save_delete_cancel';
    ModalType.TEMPLATE = 'lytix_planner/modal_save_delete_cancel';
    ModalType.prototype = Object.create(Modal.prototype);
    ModalType.prototype.constructor = ModalType;

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    ModalType.prototype.registerEventListeners = function() {
        // Apply parent event listeners.
        Modal.prototype.registerEventListeners.call(this);

        this.registerCloseOnSave();

        this.getModal().on(CustomEvents.events.activate, SELECTORS.DELETE_BUTTON, function(e, data) {
            var deleteEvent = $.Event('modal-save-delete-cancel:delete');
            this.getRoot().trigger(deleteEvent, this);

            if (!deleteEvent.isDefaultPrevented()) {
                data.originalEvent.preventDefault();

                if (this.removeOnClose) {
                    this.destroy();
                } else {
                    this.hide();
                }
            }
        }.bind(this));

        this.registerCloseOnCancel();
    };
// Automatically register with the modal registry the first time this module is imported
// so that you can create modals of this type using the modal factory.
    if (!registered) {
        ModalRegistry.register(ModalType.TYPE, ModalType, ModalType.TEMPLATE);
        registered = true;
    }

    r = ModalType.TYPE;
} else {
    // Moodle >= 4.4 .
    r = class ModalType extends Modal {
        static TYPE = 'lytix_planner-modal_save_delete_cancel';
        static TEMPLATE = 'lytix_planner/modal_save_delete_cancel';

        /**
         * Constructor for the Modal.
         *
         * @param {object} root The root jQuery element for the modal
         */
        constructor(root) {
            super(root);
            if (!this.getFooter().find(SELECTORS.SAVE_BUTTON).length) {
                Notification.exception({message: 'No save button found'});
            }

            if (!this.getFooter().find(SELECTORS.DELETE_BUTTON).length) {
                Notification.exception({message: 'No delete button found'});
            }

            if (!this.getFooter().find(SELECTORS.CANCEL_BUTTON).length) {
                Notification.exception({message: 'No cancel button found'});
            }
        }

        /**
         * Set up all of the event handling for the modal.
         *
         * @method registerEventListeners
         */
        registerEventListeners() {
            // Apply parent event listeners.
            super.registerEventListeners(this);

            super.registerCloseOnSave();

            this.getModal().on(CustomEvents.events.activate, SELECTORS.DELETE_BUTTON, function(e, data) {
                var deleteEvent = $.Event('modal-save-delete-cancel:delete');
                this.getRoot().trigger(deleteEvent, this);

                if (!deleteEvent.isDefaultPrevented()) {
                    data.originalEvent.preventDefault();

                    if (this.removeOnClose) {
                        this.destroy();
                    } else {
                        this.hide();
                    }
                }
            }.bind(this));

            super.registerCloseOnCancel();
        }
    };
    r.registerModalType();
}
export default r;
