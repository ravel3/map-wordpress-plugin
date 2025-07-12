/**
 * Medal Inline Edit JavaScript
 * Handles inline editing of medals in the table
 */

jQuery(document).ready(function($) {
    'use strict';
    
    var editingMedalId = null;
    var originalValues = {};
    
    // Add new medal row
    $('#add-medal').click(function() {
        var newRow = createNewMedalRow();
        $('.wp-list-table tbody').prepend(newRow);
    });
    
    // Edit medal
    $(document).on('click', '.edit-medal', function(e) {
        e.preventDefault();
        var medalId = $(this).data('id');
        var row = $(this).closest('tr');
        startEditing(row);
    });
    
    // Save medal
    $(document).on('click', '.save-medal', function(e) {
        e.preventDefault();
        var medalId = $(this).data('id');
        var row = $(this).closest('tr');
        saveMedal(row);
    });
    
    // Cancel edit
    $(document).on('click', '.cancel-edit', function(e) {
        e.preventDefault();
        var medalId = $(this).data('id');
        var row = $(this).closest('tr');
        cancelEditing(row);
    });
    
    function createNewMedalRow() {
        var mapId = new URLSearchParams(window.location.search).get('map_id');
        var newRow = $('<tr class="new-medal-row editing">' +
            '<td><input type="text" class="medal-name-input" value="" style="width: 100%;" placeholder="Nazwa medalu"></td>' +
            '<td><textarea class="medal-description-input" style="width: 100%; height: 60px;" placeholder="Opis medalu"></textarea></td>' +
            '<td><input type="text" class="medal-pk-no-input" value="" style="width: 100%;" placeholder="Numer PK"></td>' +
            '<td><div class="medal-coordinates-inputs">' +
                '<input type="number" class="medal-x-coord-input" value="0" style="width: 60px;" placeholder="X"> ' +
                '<input type="number" class="medal-y-coord-input" value="0" style="width: 60px;" placeholder="Y">' +
            '</div></td>' +
            '<td><input type="number" class="medal-total-input" value="1" style="width: 60px;" min="1"></td>' +
            '<td><input type="number" class="medal-available-input" value="1" style="width: 60px;" min="0"></td>' +
            '<td><span class="medal-last-taken" data-medal-id="new">Nigdy</span></td>' +
            '<td><div class="medal-actions" data-medal-id="new">' +
                '<a href="#" class="button button-small save-medal" data-id="new">Zapisz</a> ' +
                '<a href="#" class="button button-small cancel-edit" data-id="new">Anuluj</a>' +
            '</div></td>' +
            '<input type="hidden" class="medal-map-id" value="' + mapId + '">' +
        '</tr>');
        
        // Set editing state
        editingMedalId = 'new';
        
        // Focus on name field
        setTimeout(function() {
            newRow.find('.medal-name-input').focus();
        }, 100);
        
        return newRow;
    }
    
    function startEditing(row) {
        if (editingMedalId !== null) {
            cancelEditing($('[data-medal-id="' + editingMedalId + '"]').closest('tr'));
        }
        
        var medalId = row.find('[data-medal-id]').first().data('medal-id');
        editingMedalId = medalId;
        
        // Store original values
        originalValues[medalId] = {};
        row.find('[data-medal-id]').each(function() {
            var field = $(this);
            var fieldName = field.attr('class').replace('medal-', '');
            originalValues[medalId][fieldName] = field.text().trim();
        });
        
        // Convert spans to input fields
        row.find('.medal-name').replaceWith('<input type="text" class="medal-name-input" value="' + (originalValues[medalId].name || '') + '" style="width: 100%;" placeholder="Nazwa medalu">');
        row.find('.medal-description').replaceWith('<textarea class="medal-description-input" style="width: 100%; height: 60px;" placeholder="Opis medalu">' + (originalValues[medalId].description || '') + '</textarea>');
        row.find('.medal-pk-no').replaceWith('<input type="text" class="medal-pk-no-input" value="' + (originalValues[medalId]['pk-no'] || '') + '" style="width: 100%;" placeholder="Numer PK">');
        
        // Handle coordinates
        var coords = originalValues[medalId].coordinates.match(/X: (\d+), Y: (\d+)/);
        var xCoord = coords ? coords[1] : '0';
        var yCoord = coords ? coords[2] : '0';
        row.find('.medal-coordinates').replaceWith(
            '<div class="medal-coordinates-inputs">' +
            '<input type="number" class="medal-x-coord-input" value="' + xCoord + '" style="width: 60px;" placeholder="X"> ' +
            '<input type="number" class="medal-y-coord-input" value="' + yCoord + '" style="width: 60px;" placeholder="Y">' +
            '</div>'
        );
        
        row.find('.medal-total').replaceWith('<input type="number" class="medal-total-input" value="' + (originalValues[medalId].total || '1') + '" style="width: 60px;" min="1">');
        row.find('.medal-available').replaceWith('<input type="number" class="medal-available-input" value="' + (originalValues[medalId].available || '1') + '" style="width: 60px;" min="0">');
        
        // Show/hide buttons
        row.find('.edit-medal').hide();
        row.find('.save-medal, .cancel-edit').show();
        
        // Add editing class to row
        row.addClass('editing');
        
        // Focus on name field
        row.find('.medal-name-input').focus();
    }
    
    function saveMedal(row) {
        var medalId = row.find('[data-medal-id]').first().data('medal-id');
        var mapId = row.find('.medal-map-id').val() || new URLSearchParams(window.location.search).get('map_id');
        
        // Collect form data
        var formData = {
            nonce: medalMapAdmin.nonce,
            map_id: mapId,
            name: row.find('.medal-name-input').val().trim(),
            description: row.find('.medal-description-input').val().trim(),
            pk_no: row.find('.medal-pk-no-input').val().trim(),
            x_coordinate: parseInt(row.find('.medal-x-coord-input').val()) || 0,
            y_coordinate: parseInt(row.find('.medal-y-coord-input').val()) || 0,
            total_medals: parseInt(row.find('.medal-total-input').val()) || 1,
            available_medals: parseInt(row.find('.medal-available-input').val()) || 1
        };
        
        // Validate required fields
        if (!formData.name) {
            alert('Nazwa medalu jest wymagana!');
            row.find('.medal-name-input').focus();
            return;
        }
        
        if (formData.x_coordinate < 0 || formData.y_coordinate < 0) {
            alert('Współrzędne muszą być większe lub równe 0!');
            return;
        }
        
        // Determine action
        if (medalId === 'new') {
            formData.action = 'medal_map_add_medal';
        } else {
            formData.action = 'medal_map_edit_medal';
            formData.medal_id = medalId;
        }
        
        // Send AJAX request
        $.ajax({
            url: medalMapAdmin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            success: function(response) {
                if (response.success) {
                    if (medalId === 'new') {
                        // Reload page to show new medal with proper ID
                        location.reload();
                    } else {
                        // Update the row with new values
                        var updateData = {
                            medal_id: medalId,
                            name: formData.name,
                            description: formData.description,
                            pk_no: formData.pk_no,
                            x_coordinate: formData.x_coordinate,
                            y_coordinate: formData.y_coordinate,
                            total_medals: formData.total_medals,
                            available_medals: formData.available_medals
                        };
                        updateRowWithValues(row, updateData);
                        finishEditing(row);
                    }
                } else {
                    alert('Błąd: ' + response.data);
                }
            },
            error: function() {
                alert('Błąd podczas zapisywania medalu');
            }
        });
    }
    
    function updateRowWithValues(row, data) {
        // Convert input fields back to spans with updated values
        row.find('.medal-name-input').replaceWith('<span class="medal-name" data-medal-id="' + data.medal_id + '">' + data.name + '</span>');
        row.find('.medal-description-input').replaceWith('<span class="medal-description" data-medal-id="' + data.medal_id + '">' + data.description + '</span>');
        row.find('.medal-pk-no-input').replaceWith('<span class="medal-pk-no" data-medal-id="' + data.medal_id + '">' + data.pk_no + '</span>');
        row.find('.medal-coordinates-inputs').replaceWith('<span class="medal-coordinates" data-medal-id="' + data.medal_id + '">X: ' + data.x_coordinate + ', Y: ' + data.y_coordinate + '</span>');
        row.find('.medal-total-input').replaceWith('<span class="medal-total" data-medal-id="' + data.medal_id + '">' + data.total_medals + '</span>');
        row.find('.medal-available-input').replaceWith('<span class="medal-available" data-medal-id="' + data.medal_id + '">' + data.available_medals + '</span>');
    }
    
    function cancelEditing(row) {
        var medalId = row.find('[data-medal-id]').first().data('medal-id');
        
        if (medalId === 'new') {
            // Remove the new row
            row.remove();
        } else {
            // Restore original values
            if (originalValues[medalId]) {
                row.find('.medal-name-input').replaceWith('<span class="medal-name" data-medal-id="' + medalId + '">' + originalValues[medalId].name + '</span>');
                row.find('.medal-description-input').replaceWith('<span class="medal-description" data-medal-id="' + medalId + '">' + originalValues[medalId].description + '</span>');
                row.find('.medal-pk-no-input').replaceWith('<span class="medal-pk-no" data-medal-id="' + medalId + '">' + originalValues[medalId]['pk-no'] + '</span>');
                row.find('.medal-coordinates-inputs').replaceWith('<span class="medal-coordinates" data-medal-id="' + medalId + '">' + originalValues[medalId].coordinates + '</span>');
                row.find('.medal-total-input').replaceWith('<span class="medal-total" data-medal-id="' + medalId + '">' + originalValues[medalId].total + '</span>');
                row.find('.medal-available-input').replaceWith('<span class="medal-available" data-medal-id="' + medalId + '">' + originalValues[medalId].available + '</span>');
            }
        }
        
        finishEditing(row);
    }
    
    function finishEditing(row) {
        // Show/hide buttons
        row.find('.edit-medal').show();
        row.find('.save-medal, .cancel-edit').hide();
        
        // Remove editing class
        row.removeClass('editing');
        
        editingMedalId = null;
    }
}); 