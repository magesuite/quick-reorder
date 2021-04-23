define([
    'jquery',
    'mage/template',
    'Magento_Customer/js/customer-data',
    'text!MageSuite_QuickReorder/template/reorder-banner.html',
    'jquery-ui-modules/widget',
    'mage/translate',
], function($, mageTemplate, customerData, modalTemplate) {
    'use strict';

    $.widget('magesuite.reorderBanner', {
        options: {
            welcomeText: '<span>Welcome back %name,</span><br><span>Would you like to reorder your last purchase?</span>',
            lastOrderText: 'Your last %link order',
            buttonText: 'Reorder',
            showLastOrderedItems: true,
            timeoutToShowBanner: 3000
        },
        _create: function() {
            if (
                sessionStorage.getItem('magesuite-reorder-banner-close') ||
                sessionStorage.getItem('magesuite-reorder-banner-clicked')
            ) {
                return false;
            }

            this.customerInfo = customerData.get('reorder-banner')();

            if (this.customerInfo.lastOrderReorderLink) {
                $('.page-wrapper').prepend(
                    mageTemplate(modalTemplate)({
                        data: this._prepareBannerData(),
                    })
                );

                this.$reorderBanner = $('.cs-reorder-banner');

                this._handleInitialShow();
                this._attachEvents();
            }
        },
        _handleInitialShow: function() {
            var widget = this;
            var $reorderBanner = this.$reorderBanner;

            // Timeout is set to wait until assets are load and browser is ready to display transition smoothly
            // Transition is added to better catch users' attention (only for te first time)

            if (sessionStorage.getItem('magesuite-reorder-banner-shown')) {
                this.$reorderBanner.addClass(
                    'cs-reorder-banner--display cs-reorder-banner--show'
                );
            } else {
                setTimeout(function() {
                    $reorderBanner.addClass('cs-reorder-banner--show');
                    sessionStorage.setItem(
                        'magesuite-reorder-banner-shown',
                        'true'
                    );
                }, widget.options.timeoutToShowBanner);
            }

            $('body').addClass('reorder-banner-visible');
        },
        _attachEvents: function() {
            var $reorderBanner = this.$reorderBanner;
            $('.cs-reorder-banner__close').on('click', function() {
                $reorderBanner.removeClass('cs-reorder-banner--show');
                $('body').removeClass('reorder-banner-visible');
                sessionStorage.setItem(
                    'magesuite-reorder-banner-close',
                    'true'
                );
            });

            $('.cs-reorder-banner__button').on('click', function(e) {
                e.preventDefault();
                sessionStorage.setItem(
                    'magesuite-reorder-banner-clicked',
                    'true'
                );

                $('.cs-reorder-banner__form').submit();
            });
        },
        _prepareBannerData: function() {
            var welcomeText = $.mage.__(this.options.welcomeText);
            welcomeText = welcomeText.replace(
                '%name',
                this.customerInfo.firstname
            );

            var lastOrderText = $.mage.__(this.options.lastOrderText);
            lastOrderText = lastOrderText.replace(
                '%link',
                '<a href="' + this.customerInfo.lastOrderViewLink + '" class="cs-reorder-banner__link">'
            ) + '</a>';

            var productsCount = this.customerInfo.lastOrderItemsCount + ' ' +
            (this.customerInfo.lastOrderItemsCount > 1
                ? $.mage.__('products')
                : $.mage.__('product'));

            var lastOrderedItems = this.customerInfo.lastOrderItems.map(function(value, index, array) {
                if (index < 2) {
                    var name = value.name;
                    if (value.name.length > 26) {
                        name = name.substring(0, 26) + '...';
                    }

                    return '<span>' + name + '<span class="cs-reorder-banner__item-count">' + value.count + 'x</span><span><br>';
                } else if (index === 2) {
                    return (array.length - 2) + ' ' + $.mage.__('more') + '...';
                } else {
                    return '';
                }
            });

            lastOrderedItems = lastOrderedItems.join('');

            return {
                welcomeText: welcomeText,
                lastOrderText: lastOrderText,
                lastOrderReorderLink: this.options.showLastOrderedItems ? this.customerInfo.lastOrderReorderLink: '',
                amount:  this.customerInfo.lastOrderAmount,
                productsCount: productsCount,
                lastOrderedItems: lastOrderedItems,
                buttonText: $.mage.__(this.options.buttonText),
                closeText: $.mage.__('Close'),
            };
        },
    });

    return $.magesuite.reorderBanner;
});
