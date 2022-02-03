define([
    'jquery',
    'mage/template',
    'Magento_Customer/js/customer-data',
    'text!MageSuite_QuickReorder/template/reorder-banner.html',
    'jquery-ui-modules/widget',
    'mage/translate'
], function($, mageTemplate, customerData, modalTemplate) {
    'use strict';

    $.widget('magesuite.reorderBanner', {
        options: {
            welcomeText: $.mage.__('Welcome back %name'),
            reorderQuestion: $.mage.__('Would you like to reorder your last purchase?'),
            lastOrderText: $.mage.__('Your last %link order'),
            buttonText: $.mage.__('Reorder'),
            listSubheadlineText: $.mage.__('%qty more...'),
            showLastOrderedItems: true,
            maxProductNameLength: 26, // cut product name after x characters to save space
            timeoutToShowBanner: 3000
        },
        /**
         * Init reorder banner only if user have not closed recently (with treshold set in days in Admin panel, default: 7),
         * or used it during the current session.
         * Get reorder-banner data from customerData then prepare and append html
         */
        _create: function() {
            if (
                (localStorage.getItem('magesuite-reorder-banner-close')
                    && Date.now() < Number(localStorage.getItem('magesuite-reorder-banner-close-time')) + this.options.hideTime * 86400000) ||
                localStorage.getItem('magesuite-reorder-banner-used')
            ) {
                return false;
            }

            this.customerInfo = customerData.get('reorder-banner')();

            this._initReorderBanner();
        },
        /**
         * Sometimes customerData are not available in the moment reorder banner is created
         * In such case subscribe to customerData to be able to display it later, when data appear
         */
        _initReorderBanner() {
            if (!this.customerInfo.lastOrderReorderLink) {
                customerData.get('reorder-banner').subscribe(function(data) {
                      this.customerInfo = data;
                      this._initReorderBanner();
                }.bind(this));
       
                return
            }
       
            $('.page-wrapper').prepend(
                mageTemplate(modalTemplate)({
                    data: this._prepareBannerData(),
                })
            );

            this.$reorderBanner = $('.cs-reorder-banner');

            this._handleInitialShow();
            this._attachEvents();
       },
    
        /**
         * When the banner is about to be shown for the first time in a current session
         * show banner with delay and with a sliding animation to catch user's attention.
         * Do not animate and delay banner again anymore because it can be too annoying to the user.
         * Set 'magesuite-reorder-banner-shown' item in localStorage to define that banner was already shown with animation
         */
        _handleInitialShow: function() {
            var widget = this;
            var $reorderBanner = this.$reorderBanner;

            // Timeout is set to wait until assets are loaded and browser is ready to display transition smoothly
            // Transition is added to better catch users' attention (only for te first time)

            if (localStorage.getItem('magesuite-reorder-banner-shown')) {
                this.$reorderBanner.addClass(
                    'cs-reorder-banner--display cs-reorder-banner--show'
                );
            } else {
                setTimeout(function() {
                    $reorderBanner.addClass('cs-reorder-banner--show');
                    localStorage.setItem(
                        'magesuite-reorder-banner-shown',
                        'true'
                    );
                }, widget.options.timeoutToShowBanner);
            }

            $('body').addClass('reorder-banner-visible');
        },
        /**
         * Close banner after click on X icon and do not show it again in the current session -
         * set 'magesuite-reorder-banner-close' entry in localStorage.
         * Submit reorder form to add products to the cart. Then set 'magesuite-reorder-banner-used' entry and
         * do not show banner again.
         */
        _attachEvents: function() {
            var $reorderBanner = this.$reorderBanner;
            $('.cs-reorder-banner__close').on('click', function() {
                $reorderBanner.removeClass('cs-reorder-banner--show');
                $('body').removeClass('reorder-banner-visible');
                localStorage.setItem(
                    'magesuite-reorder-banner-close',
                    'true'
                );
                localStorage.setItem(
                    'magesuite-reorder-banner-close-time',
                    Date.now()
                );
            });

            $('.cs-reorder-banner__button').on('click', function(e) {
                e.preventDefault();
                localStorage.setItem(
                    'magesuite-reorder-banner-used',
                    'true'
                );
                localStorage.removeItem('magesuite-reorder-banner-close');
                localStorage.removeItem('magesuite-reorder-banner-close-time');
                localStorage.removeItem('magesuite-reorder-banner-shown');

                $('.cs-reorder-banner__form').submit();
            });
        },
        /**
         * Add welcome text:
         * "Welcome back John, Would you like to reorder your last purchase?"
        */
        _prepareWelcomeText: function() {
            var welcomeText = this.options.welcomeText;
            var reorderQuestion = this.options.reorderQuestion;

            welcomeText = welcomeText.replace(
                '%name',
                this.customerInfo.firstname
            );

            var outputHtml = "<strong>"
                + welcomeText
                + ",</strong></br>"
                + "<span>"
                + reorderQuestion
                + "</span>"

            return outputHtml;
        },
        /**
         * Add last order text:
         * "Your last order 34.55 EUR"
         * Order is a link leading to last order in customer dashboard (user area)
        */
        _prepareLastOrderText: function() {
            var lastOrderText = this.options.lastOrderText;
            lastOrderText = lastOrderText.replace(
                '%link',
                '<a href="' + this.customerInfo.lastOrderViewLink + '" class="cs-reorder-banner__link">'
            ) + '</a>';

             return lastOrderText ;
        },
        /**
         * Add text with last order products:
         * Show 2 first products with amount of items
         * Add link "X more" if there is more products
        */
        _prepareLastOrderItems: function() {
            var $widget = this;
            var lastOrderedItems = this.customerInfo.lastOrderItems.map(function(value, index, array) {
                if (index < 2) {
                    var name = value.name;
                    if (value.name.length > $widget.options.maxProductNameLength) {
                        name = name.substring(0, $widget.options.maxProductNameLength) + '...';
                    }

                    return '<span>' + name + '<span class="cs-reorder-banner__item-count">' + value.count + 'x</span><span><br>';
                } else if (index === 2) {
                    return $widget.options.listSubheadlineText.replace(
                            '%qty',
                            array.length - 2
                        );
                } else {
                    return '';
                }
            });

            return lastOrderedItems.join('');
        },
        /**
         * Gather all data that are needed in html template
        */
        _prepareBannerData: function() {
            var itemsCount = this.customerInfo.lastOrderItemsCount;

            return {
                welcomeText: this._prepareWelcomeText(),
                lastOrderText: this._prepareLastOrderText(),
                lastOrderReorderLink: this.customerInfo.lastOrderReorderLink,
                amount:  this.customerInfo.lastOrderAmount,
                productsCount: itemsCount + ' ' + (itemsCount > 1 ? $.mage.__('products') : $.mage.__('product')),
                lastOrderedItems: this.options.showLastOrderedItems ? this._prepareLastOrderItems(): '',
                buttonText: $.mage.__(this.options.buttonText),
                closeText: $.mage.__('Close'),
            };
        },
    });

    return $.magesuite.reorderBanner;
});
