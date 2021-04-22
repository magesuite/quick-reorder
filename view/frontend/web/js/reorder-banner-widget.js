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
        },
        _create: function() {
            console.log('create reorder');
            if (
                sessionStorage.getItem('magesuite-reorder-banner-close') ||
                sessionStorage.getItem('magesuite-reorder-banner-clicked')
            ) {
                return false;
            }

            this.customerInfo = customerData.get('reorder-banner')();

            this.customerInfo = {
                lastOrderReorderLink: 'sasasas',
                firstname: 'John',
                lastOrderViewLink: 'asasasa',
                lastOrderItemsCount: 2,
                lastOrderAmount: '34.55E',
                lastOrderItems: [
                    {
                        name: 'Air Optix plus Hydraglyde',
                        count: 6
                    },
                    {
                        name: 'OPTI-FREE PUREMOIST 3x300ml & 90ml',
                        count: 1
                    }
                ]
            }

            if (this.customerInfo.lastOrderReorderLink) {
                $('.page-wrapper').prepend(
                    mageTemplate(modalTemplate)({
                        data: this._prepareBannerData(),
                    })
                );

                var $reorderBanner = $('.cs-reorder-banner');

                // Timeout is set to wait until assets are load and browser is ready to display transition smoothly
                // Transition is added to better catch users' attention (only for te first time)

                if (sessionStorage.getItem('magesuite-reorder-banner-shown')) {
                    $reorderBanner.addClass(
                        'cs-reorder-banner--display cs-reorder-banner--show'
                    );
                } else {
                    setTimeout(function() {
                        $reorderBanner.addClass('cs-reorder-banner--show');
                        sessionStorage.setItem(
                            'magesuite-reorder-banner-shown',
                            'true'
                        );
                    }, 3000);
                }

                $('body').addClass('reorder-banner-visible');

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
            }
        },
        _prepareBannerData: function() {
            var welcomeText = $.mage.__(this.options.welcomeText);
            welcomeText = welcomeText.replace(
                '%name',
                this.customerInfo.firstname
            );
            var lastOrderText =
                $.mage.__('Your last') +
                ' <a href="' +
                this.customerInfo.lastOrderViewLink +
                '" class="cs-reorder-banner__link">' +
                $.mage.__('order') + '</a>';

            var productsCount = this.customerInfo.lastOrderItemsCount + ' ' +
            (this.customerInfo.lastOrderItemsCount > 1
                ? $.mage.__('products')
                : $.mage.__('product'));

            var lastOrderedItems = this.customerInfo.lastOrderItems.map(function(value, index, array) {
                if(index < 2) {
                    return value.name + ' <span>' + value.count + 'x</span><br>';
                } else if(index === 2) {
                    return (array.length - 2) + ' ' + $.mage.__('more') + '...';
                } else {
                    return '';
                }
            });

            lastOrderedItems = lastOrderedItems.join('');

            return {
                welcomeText: welcomeText,
                lastOrderText: lastOrderText,
                lastOrderReorderLink: this.customerInfo.lastOrderReorderLink,
                amount:  this.customerInfo.lastOrderAmount,
                productsCount: productsCount,
                lastOrderedItems: lastOrderedItems,
                buttonText: $.mage.__('Reorder'),
                closeText: $.mage.__('Close'),
            };
        },
    });

    return $.magesuite.reorderBanner;
});
