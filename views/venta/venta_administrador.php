<style>
    .btn {
        margin-right: 10px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th, .table td {
        padding: 8px; 
        border: 1px solid #ddd; 
    }

    .table th {
        background-color: #f2f2f2; 
    }

    .table tbody tr:nth-child(even) {
        background-color: #f9f9f9; 
    }

    .btn-danger, .btn-primary {
        padding: 8px 12px; 
        border: none; 
        border-radius: 5px; 
        cursor: pointer; 
    }

    .btn-danger:hover, .btn-primary:hover {
        opacity: 0.8; 
    }

    .btn-danger i, .btn-primary i {
        margin-right: 5px; 
    }

    .float-right {
        float: right; 
    }

    .mr-1 {
        margin-right: 10px;
    }

    .mr-4 {
        margin-right: 20px; 
    }
</style>


<div class="content-wrapper" id="contenidoAdmin">
    <div class ="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-5">
                    <h1 class="m-0"><?php echo $data["titulo"];?></h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3">
                    <label for="fecha_inicial">Fecha Inicial:</label>
                    <input type="date" id="fecha_inicial" name="fecha_inicial" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="fecha_final">Fecha Final:</label>
                    <input type="date" id="fecha_final" name="fecha_final" class="form-control">
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label>
                    <input type="submit" id="busquedafechas" name="busquedafechas" value = "Buscar entre fechas" class="form-control btn btn-primary">
                </div>
                <div class="col-md-3">
                    <label for="fecha_final">&nbsp;</label>
                    <input type="submit" id="mostrartodas" name="mostrartodas" value = "Mostrar todas" class="form-control btn btn-primary">
                </div>
            </div>
        </div>
    </div>
    
    <div class="content mt-4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="table table-responsive">
                    <table class="table" id="tbl-VentasAdmin">
                        <thead>
                            <tr>
                                <th>ID Venta</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($data["resultado"] as $ventas ) : ?>
                            <tr>
                                <td><?php echo $ventas["iVenID"]; ?></td>
                                <td><?php echo $ventas["cliente"]; ?></td>
                                <td><?php echo $ventas["dPedFecha"];?></td>
                                <td><?php echo $ventas["fPedTotal"];?></td>
                                <td><?php echo $ventas["tVenEstado"];?></td>
                                <td>
                                    <a href="#" class="btn btn-danger deleteBtn" data-toggle="modal" data-target="#deleteModal" data-recordid="<?php echo $ventas["iVenID"]; ?>"><i class="fas fa-trash"></i></a>
                                    <a href="index.php?c=VentaController&a=generarTicketVenta&id=<?php echo $ventas["iVenID"]; ?>" class="btn btn-danger reportBtnPDF" target="_blank"><i class="fas fa-file-pdf"></i></a>
                                    <a href="#" class="btn btn-primary reportBtnView" data-toggle="modal" data-target="#tablaModal" data-recordid="<?php echo $ventas["iVenID"]; ?>"><i class="fas fa-book"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>    
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="tablaModal" tabindex="-1" role="dialog" aria-labelledby="tablaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tablaModalLabel">Tabla de Productos</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tabla-detalle">
                            <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>ID Categoria</th>
                                <th>Nombre</th>
                                <th>Cantidad</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
document.querySelectorAll(".reportBtnView").forEach(function(button) {
    button.addEventListener("click", function() {
        var record_id = $(this).data("recordid");
        $.ajax({
            url :"index.php?c=VentaController&a=verDetalleVenta",
            method : "POST",
            data : {record_id: record_id},
            async : true,
            success : function(response){
                var respuesta = JSON.parse(response);
                if (respuesta.success) {
                    var detalleV = respuesta.detalle;
                    var tbody = $("#tabla-detalle tbody");
                    tbody.empty();
                    $.each(detalleV, function(index, detalle){
                        var fila = 
                        '<tr>'+
                            '<td>' + detalle[0] +'</td>'+
                            '<td>' + detalle[1] +'</td>'+
                            '<td>' + detalle[2] +'</td>'+
                            '<td>' + detalle[3] +'</td>'+
                        '</tr>';    
                        tbody.append(fila);
                    });
                } else {
                    alert("No existe ese detalle de venta según el id a buscar");
                }
            },
            error: function(xhr, status, error) {
                alert("Error en la solicitud AJAX: " + error);
            }
        });
    });
});

function recargarPaginaAsincronamente() {
    $.ajax({
        url: window.location.href, 
        type: 'GET',
        cache: false, 
        success: function(response) {
            $('.content .mt-4').html(response);
        },
        error: function(xhr, status, error) {
            console.error("Error en la solicitud AJAX: " + error);
        }
    });
}

setInterval(recargarPaginaAsincronamente, 1000); 

</script>


