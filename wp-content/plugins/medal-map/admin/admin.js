jQuery(document).ready(function($) {
    // Media uploader dla obrazów map
    if (typeof wp !== 'undefined' && wp.media) {
        var mediaUploader;

        $('#upload_image_button').click(function(e) {
            e.preventDefault();

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: 'Wybierz obraz mapy',
                button: {
                    text: 'Użyj tego obrazu'
                },
                multiple: false
            });

            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#map_image').val(attachment.url);

                // Automatycznie ustaw wymiary obrazu
                var img = new Image();
                img.onload = function() {
                    $('#image_width').val(this.width);
                    $('#image_height').val(this.height);
                };
                img.src = attachment.url;

                // Pokaż podgląd
                showImagePreview(attachment.url);
            });

            mediaUploader.open();
        });
    }

    // Podgląd obrazu
    function showImagePreview(url) {
        var preview = '<img src="' + url + '" class="image-preview" alt="Podgląd obrazu">';
        $('#upload_image_button').after(preview);
        $('.image-preview').not(':last').remove();
    }

    // Pokaż podgląd dla istniejącego obrazu
    if ($('#map_image').val()) {
        showImagePreview($('#map_image').val());
    }

    // Walidacja formularza
    $('form').on('submit', function(e) {
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

    // Automatyczne odświeżanie po zmianie URL obrazu
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

    // Tooltips dla pól formularza
    if (typeof tippy !== 'undefined') {
        tippy('[data-tippy-content]');
    }
});