/**
 * Map Management JavaScript
 * Handles map form validation and image preview functionality
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Image preview functionality
    function showImagePreview(url) {
        var previewContainer = $('#image_preview');
        if (previewContainer.length === 0) {
            $('<div id="image_preview" style="margin-top: 10px;"></div>').insertAfter('#map_image');
        }
        
        if (url) {
            previewContainer.html('<img src="' + url + '" style="max-width: 300px; max-height: 200px; border: 1px solid #ddd;" />');
        } else {
            previewContainer.empty();
        }
    }

    // Show image preview if URL is already set
    if ($('#map_image').val()) {
        showImagePreview($('#map_image').val());
    }

    // Map form validation - only on map form pages
    if (window.location.href.indexOf('page=medal-map-add') !== -1) {
        $('form[name="map-form"], form:has(#map_name)').on('submit', function(e) {
            var name = $('#map_name').val().trim();
            var image = $('#map_image').val().trim();

            if (!name) {
                alert('Proszę podać nazwę mapy');
                e.preventDefault();
                return false;
            }

            if (!image) {
                alert('Proszę wybrać obraz mapy');
                e.preventDefault();
                return false;
            }

            var width = parseInt($('#image_width').val());
            var height = parseInt($('#image_height').val());

            if (!width || width < 100 || width > 5000) {
                alert('Szerokość obrazu musi być między 100 a 5000 pikseli');
                e.preventDefault();
                return false;
            }

            if (!height || height < 100 || height > 5000) {
                alert('Wysokość obrazu musi być między 100 a 5000 pikseli');
                e.preventDefault();
                return false;
            }
        });
    }

    // Automatic image preview update when URL changes
    $('#map_image').on('change', function() {
        var url = $(this).val().trim();
        if (url) {
            showImagePreview(url);

            var img = new Image();
            img.onload = function() {
                $('#image_width').val(this.width);
                $('#image_height').val(this.height);
            };
            img.src = url;
        }
    });

    // Media upload button functionality
    $('#upload_image_button').click(function(e) {
        e.preventDefault();
        
        var image = wp.media({
            title: 'Wybierz obraz mapy',
            multiple: false
        }).open().on('select', function() {
            var uploaded_image = image.state().get('selection').first();
            var image_url = uploaded_image.toJSON().url;
            $('#map_image').val(image_url);
            showImagePreview(image_url);
        });
    });

    // Tooltips for form fields
    if (typeof tippy !== 'undefined') {
        tippy('[data-tippy-content]');
    }
}); 