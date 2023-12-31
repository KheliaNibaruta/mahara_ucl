/*jslint browser: true, nomen: true,  white: true */
/* global jQuery, $ */

/*
 * Customisation on top of bootstrap's modal css to allow modals to become slide-in docks
 *
 * Works with bootstraps modal html init methods with modifications:
 * * Add a modal-docked class to the modal
 * * Use data-bs-toggle="modal-docked" for the trigger (rather than data-bs-toggle="modal")
 * * Use data-bs-dismiss="modal-docked" to the modal's close button (rather than data-bs-dismiss="modal")
 */
var dock = {};
jQuery(function($) {
    "use strict";

    /*
     * Control the hiding of our dock and trigger the necessary events
     */
    dock.hide = function() {
        window.setTimeout(function(){
            $(window).trigger('colresize');
        }, 300);

        $('.modal-docked').each( function() {
            $(this).removeClass('active');
            $(this).addClass('closed');
        });

        $('body, .navbar-fixed-top').width('auto');
        $('body').removeClass('modal-open modal-open-docked');
        $('.active-block').removeClass('active-block');
    };

    /*
     * Control showing out dock and trigger the necessary events
     *
     * @param newblock | Object, replaceContent Boolean, hasContent Boolean (for prefilled modals)
     */
    dock.show = function(newblock, replaceContent, hasContent) {
        var contentArea = newblock.find('.modal-body'),
            content = this.getLoadingIndicator();

        // If we are filling the modal dynamically (c.f page builder)
        if (hasContent) {
            // Dock already has content
        }
        else {
            // Open form here even though it's currently empty (its quicker)
            newblock.find('.modal-title').html('loading');

            if (replaceContent) {
                contentArea.html(content);
            }
            else {
                contentArea.append(content);
                contentArea.find('.block-inner').addClass('d-none');
            }
        }

        // Prevent disappearing scroll bars from interfering with smooth animation
        $('body, .navbar-fixed-top').width($('body').width());
        $('body').addClass('modal-open modal-open-docked');
        newblock.removeClass('d-none').removeClass('closed').addClass('active');
    };

    dock.getLoadingIndicator = function() {
        return '<div class="modal-loading"><div class="loading-inner"><span class="icon-spinner icon-pulse icon icon-lg"></span></div></div>';
    };

    dock.init = function(scope){

        scope.find('[data-bs-toggle="modal-docked"]').on('click', function(e){
            e.preventDefault();

            var targetID = $(this).attr('data-bs-target'),
                target = $(targetID);

            if (!$(this).hasClass('no-modal')) {
                dock.show(target, false, true);
            }
        });

        scope.find('[data-bs-dismiss="modal-docked"]').on('click', function(e){
            e.preventDefault();
            dock.hide();
        });

        scope.find('.submitcancel').on('click', function(){
            if (!$('#configureblock').hasClass('active')) {
                dock.hide();
            }
        });
    };

    dock.init($(document));
});
