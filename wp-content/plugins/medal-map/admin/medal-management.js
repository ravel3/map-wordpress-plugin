/**
 * Medal Management JavaScript
 * Handles medal modal, form submission, and AJAX operations
 */

jQuery(document).ready(function($) {
    'use strict';
    
    var medalModal = $('#medal-modal');
    var medalForm = $('#medal-form');
    var modalTitle = $('#modal-title');
    var isEditing = false;

    // Open modal for adding new medal
    $('#add-medal').click(function() {
        isEditing = false;
        modalTitle.text('Dodaj Medal');
        
        // Clear form fields
        $('#medal_id').val(0);
        $('#medal_name').val('');
        $('#medal_description').val('');
        $('#medal_pk_no').val('');
        $('#medal_x_coordinate').val('');
        $('#medal_y_coordinate').val('');
        $('#medal_total_medals').val('1');
        $('#medal_available_medals').val('1');
        
        // Set map_id from URL
        var urlParams = new URLSearchParams(window.location.search);
        var mapId = urlParams.get('map_id');
        if (mapId) {
            $('#medal-modal #medal_map_id').val(mapId);
        }
        
        medalModal.show().addClass('show');
    });

    // Close modal
    $('.medal-modal-close, #cancel-edit').click(function() {
        medalModal.hide().removeClass('show');
    });

    // Close modal when clicking outside
    $(window).click(function(event) {
        if (event.target == medalModal[0]) {
            medalModal.hide().removeClass('show');
        }
    });

    // Edit medal
    $(document).on('click', '.edit-medal', function(e) {
        e.preventDefault();
        var medalId = $(this).data('id');
        
        // Set map_id from URL
        var urlParams = new URLSearchParams(window.location.search);
        var mapId = urlParams.get('map_id');
        if (mapId) {
            $('#medal-modal #medal_map_id').val(mapId);
        }
        
        $.ajax({
            url: medalMapAdmin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'medal_map_get_medal_for_edit',
                medal_id: medalId,
                nonce: medalMapAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var medal = response.data;
                    
                    isEditing = true;
                    modalTitle.text('Edytuj Medal');
                    
                    // Set form fields
                    $('#medal_id').val(medal.id);
                    $('#medal_name').val(medal.name);
                    $('#medal_description').val(medal.description);
                    $('#medal_pk_no').val(medal.pk_no);
                    $('#medal_x_coordinate').val(medal.x_coordinate);
                    $('#medal_y_coordinate').val(medal.y_coordinate);
                    $('#medal_total_medals').val(medal.total_medals);
                    $('#medal_available_medals').val(medal.available_medals);
                    
                    // Set map_id from medal data
                    if (medal.map_id) {
                        $('#medal-modal #medal_map_id').val(medal.map_id);
                    }
                    
                    medalModal.show().addClass('show');
                } else {
                    alert('Błąd: ' + response.data);
                }
            },
            error: function() {
                alert('Błąd podczas ładowania danych medalu');
            }
        });
    });

    // Handle medal form submission
    $('#medal-form').on('submit', function(e) {
        e.preventDefault();
        
        // Prevent submission if modal is not visible
        if (!medalModal.is(':visible') && !medalModal.hasClass('show')) {
            return false;
        }
        
        // Get form values
        var nameVal = $('#medal_name').val();
        var descVal = $('#medal_description').val();
        var pkVal = $('#medal_pk_no').val();
        var xVal = $('#medal_x_coordinate').val();
        var yVal = $('#medal_y_coordinate').val();
        var totalVal = $('#medal_total_medals').val();
        var availVal = $('#medal_available_medals').val();
        var mapIdVal = $('#medal_map_id').val();
        
        // Validate required fields
        if (!nameVal || nameVal.trim() === '') {
            alert('Nazwa medalu jest wymagana!');
            $('#medal_name').focus();
            return false;
        }
        
        if (!xVal || xVal < 0) {
            alert('Współrzędna X jest wymagana i musi być większa lub równa 0!');
            $('#medal_x_coordinate').focus();
            return false;
        }
        
        if (!yVal || yVal < 0) {
            alert('Współrzędna Y jest wymagana i musi być większa lub równa 0!');
            $('#medal_y_coordinate').focus();
            return false;
        }
        
        var formData = {
            nonce: medalMapAdmin.nonce,
            map_id: mapIdVal,
            name: nameVal,
            description: descVal,
            pk_no: pkVal,
            x_coordinate: xVal,
            y_coordinate: yVal,
            total_medals: totalVal,
            available_medals: availVal
        };

        if (isEditing) {
            formData.action = 'medal_map_edit_medal';
            formData.medal_id = $('#medal_id').val();
        } else {
            formData.action = 'medal_map_add_medal';
        }

        $.ajax({
            url: medalMapAdmin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            success: function(response) {
                if (response.success) {
                    medalModal.hide().removeClass('show');
                    location.reload();
                } else {
                    alert('Błąd: ' + response.data);
                }
            },
            error: function() {
                alert('Błąd podczas zapisywania medalu');
            }
        });
    });
}); 