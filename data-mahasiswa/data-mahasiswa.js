document.addEventListener('input', function() {
    var listening = parseInt(document.getElementById('listening_comprehension').value) || 0;
    var structure = parseInt(document.getElementById('structure_written_expression').value) || 0;
    var reading = parseInt(document.getElementById('reading_comprehension').value) || 0;
    var total = listening + structure + reading;
    document.getElementById('total').value = total;
});