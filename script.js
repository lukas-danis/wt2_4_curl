$(document).ready(function() {
    var tableLength = document.getElementById("myTable").rows[0].cells.length
    $('#myTable').DataTable( {
        "scrollY":        "400px",
        "scrollCollapse": true,
        "paging":         false,
        "bInfo" : false,
        "order": [[ 0, 'asc' ]],
        "columnDefs": [
            {
            targets: [ tableLength-1 ],
            order: [[ tableLength-1, "desc" ], [ tableLength-2, 'desc' ],[ 0, 'asc' ]],
        },
        {
            "orderable": false,
            "targets": 'nosort',
        }
    ],
    });

    $(".get_id").click(function(){
        var ids = $(this).data('id');
        var name = $(this).data('usname');
         $.ajax({
             url:"upload.php",
             method:'POST',
             data:{id:ids , usname:name},
             success:function(data){
                 $('#load_data').html(data);        
             }    
         })
    })
} );