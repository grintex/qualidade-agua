$(function() {
    $('.basicAutoComplete')
        .autoComplete()
        .on('autocomplete.select', function(event, item) {
            console.log('Item selected:', item);
        });
});
