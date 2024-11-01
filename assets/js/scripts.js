; (function ($) {

    $(function () {

        const superBlankInstallationHandler = window.superBlankInstallationHandler || {

            requests: [
                {
                    progress: [0, 5],
                    action: 'super_blank_step1',
                    start_message: 'Starting fresh site...'
                },
                {
                    progress: [6, 9],
                    action: 'super_blank_step1_2',
                    start_message: 'Deep cleanup...'
                },
                {
                    progress: [10, 14],
                    action: 'super_blank_step1_3',
                    start_message: 'Removing extra tables...'
                },
                {
                    progress: [15, 20],
                    action: 'super_blank_step2',
                    start_message: 'Getting design ready...'
                },
                {
                    progress: [21, 40],
                    action: 'super_blank_step3',
                    start_message: 'Activating Astra theme...'
                },
                {
                    progress: [41, 50],
                    action: 'super_blank_step5',
                    start_message: 'Installing Elementor plugin...'
                },
                {
                    progress: [51, 60],
                    action: 'super_blank_step4',
                    start_message: 'Installing WP Forms plugin...'
                },
                {
                    progress: [61, 70],
                    action: 'super_blank_step5_1',
                    start_message: 'Menu Creation...'
                },
                {
                    progress: [71, 80],
                    action: 'super_blank_step6',
                    start_message: 'Pages creation...'
                },
                {
                    progress: [81, 100],
                    action: 'super_blank_step7',
                    start_message: 'Website settings...'
                }
            ],

            installProgress: false,

            alive: true,

            lifeCycle: null,

            count: 0,

            preventRequest: false,

            installButton: null,

            confirmDialog: null,

            statusMessage: null,

            contactUsMessage: null,

            wall: null,

            attempts: 3,

            try: 1,

            beats: 0,

            pluginVersion: 0,

            productionMode: false,

            timeToRefresh: 100,

            baseInterval: 100,

            decreaseSpeedUnit: 2,

            currentProgress: 0,

            decreaseProgressUnit: 0.0008,

            progressUnit: 0.1,

            progressInterval: null,

            heartBeatInterval: 500,

            updateProgress: function (start, end) {

                this.baseInterval = 100;

                this.progressUnit = 0.1;

                const _this = this;

                let progress = start;

                clearInterval(this.progressInterval);

                this.progressInterval = setInterval(function () {

                    if (_this.currentProgress <= end) {

                        progress += _this.progressUnit;

                        _this.currentProgress = progress;

                        _this.baseInterval += _this.decreaseSpeedUnit;

                        if (_this.progressUnit > 0.05) {

                            _this.progressUnit -= _this.decreaseProgressUnit;
                        }
                    }
                }, this.baseInterval);
            },

            countProgress: function (currentStep) {

                return currentStep / (this.requests.length / 100);
            },

            sendPostRequest: function () {

                if (this.beats > this.timeToRefresh) {

                    this.beats = 0;
                    this.preventRequest = false;

                    this.consoleLog('Refresh request.');
                }

                if (this.preventRequest) return;

                if ((this.count + 1) > this.requests.length) {

                    this.consoleLog('stop');

                    this.finishInstall();

                    this.alive = false;

                    return;
                } else {

                    this.updateProgress(this.requests[this.count]['progress'][0], this.requests[this.count]['progress'][1]);
                }

                this.preventRequest = true;

                const _this = this;
                const request = this.requests[this.count];

                request.menuData = this.menuData;

                this.successNotification(request.start_message);

                request.nonce = superBlankLocalizer.nonce;

                jQuery.post(superBlankLocalizer.ajax_url, request, function (response) {

                    _this.consoleLog('response', response);

                    // 'All done'
                    if (_this.isJSON(response)) {

                        const res = JSON.parse(response);

                        if (res.status === 200) {
                            // Success

                            _this.stepSucceed(_this, res.data.message);
                        } else {

                            // set attempts
                            if (_this.try < _this.attempts) {

                                _this.consoleLog('Attempt ' + _this.try);

                                _this.preventRequest = false;
                                _this.try++
                            } else {

                                // error
                                _this.stepFailed(_this, res?.errors?.error_data, response);
                            }
                        }
                    } else {

                        if (_this.checkResponse(response)) {

                            // success
                            _this.stepSucceed(_this, 'Content Imported successfully!');
                        } else {

                            // error
                            _this.stepFailed(_this, ['Content Import Issue'], response);
                        }
                    }

                    _this.preventRequest = false;
                });

            },

            stepFailed(_this, error_data, response) {

                if (error_data) {

                    const message = 'Error on step ' + (_this.count + 1) + ': ' + error_data.join(',');

                    _this.failedNotification(message);
                }

                _this.consoleLog('Here is an error: ', response);

                _this.alive = false;

                _this.finishInstall(true);
            },

            stepSucceed(_this, message) {

                _this.successNotification(message);

                _this.count++;

                // Refresh attempts
                _this.try = 1;

                // Refresh beats
                _this.beats = 0
            },

            checkResponse(serverResponse) {

                return serverResponse.includes("Have fun!");
            },

            startInstall: function () {

                this.installButton.text("Installing...").prop('disabled', true);
            },

            showAlert: function (message, type = 'success') {

                clearTimeout(this.alertTimeout);

                const alertSelector = '.wp-packages-alert';

                if (jQuery('body').find(alertSelector).length !== 0) {

                    jQuery('body').find(alertSelector).remove();
                }

                const alert = jQuery('<div class="wp-packages-alert alert-type-' + type + '"><p>' + message + '</p></div>');

                jQuery('body').append(alert);

                const button = jQuery('<button>x</button>');

                jQuery(alertSelector).append(button);

                button.on('click', function () {

                    jQuery('body').find(alertSelector).remove();
                });

                this.alertTimeout = setTimeout(function () {

                    if (jQuery('body').find(alertSelector).length !== 0) {

                        jQuery('body').find(alertSelector).remove();
                    }
                }, 3000);
            },

            finishInstall: function (failed = false) {

                if (failed) {

                    this.installButton.text("Failed");
                    return
                }

                if ((this.count + 1) >= this.requests.length) {

                    console.log('success');

                    this.successNotification('All done! Your new website is ready.');
                    this.installButton.text("View website").prop('disabled', false);
                    this.installButton.off('click').on('click', function () {
                        window.open(superBlankLocalizer.site_url, '_blank');
                    });
                } else {

                    this.installButton.text("Failed");
                }

                window.removeEventListener('beforeunload', this.preventClosing);
            },

            heartBeat: function () {

                const _this = this;

                // start install
                _this.startInstall();

                // start immediately
                _this.runRequestBody(_this);

                this.lifeCycle = setInterval(function () {

                    _this.runRequestBody(_this);
                }, _this.heartBeatInterval);
            },

            runRequestBody: function (_this) {

                if (!_this.alive) {
                    clearInterval(_this.lifeCycle);
                    return;
                }

                // step
                if ((_this.count + 1) <= _this.requests.length) {

                    _this.beats++;

                    _this.consoleLog('Beat. (v-' + _this.pluginVersion + ') Current step is: ', _this.count + 1);
                }

                // make requests
                _this.sendPostRequest();
            },

            failedNotification: function (message) {

                if (!this.statusMessage) return;

                this.statusMessage.text(message);
                this.statusMessage.addClass('super-blank-warning');

                // contact us message
                this.confirmDialog.append(this.contactUsMessage);
            },

            successNotification: function (message) {

                if (!this.statusMessage) return;

                this.statusMessage.text(message);
                // this.statusMessage.css('border-left-color', '#00a32a');
                this.statusMessage.removeClass('super-blank-warning');
            },

            setProgressBar: function (width, force = false, failed = false) {

                if (!this.confirmDialog) return;

                const progressBar = this.confirmDialog.find('#progress-bar-container');

                if (progressBar.length === 0) {

                    this.confirmDialog.append('<div id="progress-bar-container"><div id="progress-bar" style="width: 0%"></div></div>');
                }

                if (!width) {

                    width = '0%';
                }

                if (force) {

                    jQuery('#progress-bar').hide();
                }

                jQuery('#progress-bar').css('width', width);

                if (failed) {

                    clearInterval(this.progressInterval);

                    jQuery('#progress-bar')
                        .css('background-color', '#dc3232')
                        .css('width', '100%')
                        .css('transition', 'none');
                }

                if (parseInt(width) >= 90) {

                    jQuery('#progress-bar').css('transition', 'none');
                }
            },

            setStepProgressBar: function (percentage = 0) {

                let width = parseInt(this.beats / (this.timeToRefresh / 100)) + '%';

                if (percentage) {
                    width = parseInt(percentage) + '%';
                }

                if (!this.statusMessage) return;

                const progressBar = this.statusMessage.find('#wp-pack-step-progress');

                if (progressBar.length === 0) {

                    this.statusMessage.append('<div id="wp-pack-step-progress"></div>');
                }

                if (!width) {
                    width = '0%';
                }

                jQuery('#wp-pack-step-progress').animate({ 'width': width }, 300);
            },

            isJSON: function (str) {

                try {

                    JSON.parse(str);
                } catch (e) {

                    return false;
                }
                return true;
            },

            preventClosing: function (e) {

                e.preventDefault();
                e.returnValue = '';
            },

            consoleLog: function (...messages) {

                if (!this.productionMode) {

                    console.log(...messages);
                }
            },

            consoleError: function (...messages) {

                if (!this.productionMode) {

                    console.error(...messages);
                }
            },

            init: function (dialog, installButton) {

                if (this.installProgress) return

                this.installProgress = true

                this.productionMode = superBlankLocalizer.productionMode

                this.menuData = superBlankLocalizer.menuData

                this.installButton = installButton;

                this.confirmDialog = jQuery(dialog);

                this.statusMessage = jQuery("#status-message");

                this.contactUsMessage = '<p class="additional-status-message">Please refresh this page and try again. If you keep getting this error, go to tyler.com and click Feedback on the left to get assistance from us. We reply within 24 hours.</p>',

                this.pluginVersion = superBlankLocalizer.plugin_version;

                this.heartBeat();

                // Ask not reload the page
                window.addEventListener('beforeunload', this.preventClosing);
            }
        };

        $('#super-blank-install').on('click', function (e) {

            e.preventDefault();

            superBlankInstallationHandler.init('.super-blank-wrap', $(this));
        });

    });
})(jQuery);
