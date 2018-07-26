$(function(){
    
    $('body').on('click','#prev', function(event) {
        event.preventDefault();
        pagination.updatePagination($(this).attr('data-loop'), 'prev');
    });

    $('body').on('click','#next', function(event) {
        event.preventDefault();
        pagination.updatePagination($(this).attr('data-loop'), 'next');
    });
    
    var pagination = {
        updatePagination : function(data, event) {
            if(event === 'prev') {
                if(data != 0) this.prevElems(data);
                else console.info('Aucun élément précédent.');
            }

            if(event === 'next') {
                if(data != 0) this.nextElems(data);
                else console.info('Aucun élément suivant.');
            }
        },
        nextElems : function(data) {
            var i = $('#next').attr('data-loop');
            var max = $('#next').attr('data-max');

            if( i < max) {
                $('.li-pagination-'+i).addClass('hidden');
                $('.li-pagination-'+(parseInt(i)+1)).removeClass('hidden');

                $('#prev').attr('data-loop', i);
                $('#next').attr('data-loop', (parseInt(i)+1));	
            } else console.info('Impossible d\'aller plus loin.');
        },
        prevElems : function(max){
            var i = $('#prev').attr('data-loop');

            $('.li-pagination-'+(parseInt(i)+1)).addClass('hidden');
            $('.li-pagination-'+i).removeClass('hidden');

            $('#prev').attr('data-loop', (parseInt(i)-1));
            $('#next').attr('data-loop', i);
        }
    };
});