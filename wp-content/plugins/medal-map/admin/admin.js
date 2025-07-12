/**
 * Medal Map Admin JavaScript
 * Core functionality and conditional loading of specific modules
 */

jQuery(document).ready(function($) {
    'use strict';

    // Conditional loading of medal management scripts
    if (window.location.href.indexOf('page=medal-map-medals') !== -1) {
        // Load medal modal CSS
        if (!$('link[href*="medal-modal.css"]').length) {
            $('<link>', {
                rel: 'stylesheet',
                type: 'text/css',
                href: medalMapAdmin.plugin_url + 'medal-modal.css'
            }).appendTo('head');
        }
        
        // Load medal modal HTML
        $.get(medalMapAdmin.plugin_url + 'medal-modal.html', function(html) {
            $('body').append(html);
            
            // Set the map_id in the modal form
            var urlParams = new URLSearchParams(window.location.search);
            var mapId = urlParams.get('map_id');
            if (mapId) {
                $('#medal-modal #medal_map_id').val(mapId);
            }
            
            // Load medal management JavaScript
            $.getScript(medalMapAdmin.plugin_url + 'medal-management.js');
        });
    }
    

});