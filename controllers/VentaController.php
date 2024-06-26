<?php

require("pdf/code128.php");
class VentaController {
    protected $venta;
    protected $pedido;
    protected $platos;
    protected $clientes;

    private $pdf;
    public function __construct() {
        if ( session_status() == PHP_SESSION_NONE){
            session_start();
        }
        require_once "models/VentaModel.php";
        require_once "models/PlatoModel.php";
        require_once "models/ClienteModel.php";
        $this->venta = new VentaModel();
        $this->platos = new PlatoModel();
        $this->clientes = new ClienteModel();
        $this->pdf = new PDF_Code128('P','mm',array(80,258));
    }

    public function index(): void {
        $carID = isset($_SESSION["trabajador"]["iCarID"]) ? intval($_SESSION["trabajador"]["iCarID"]) : 0;
        if ($carID == 1) {
            $this->verVentasAdministrador();
        } elseif ($carID == 2) {
            $this->verPedidosCajero();
        } elseif ($carID == 3) {
            $this->realizarPedido(); 
        } else {
            $this->showError404();
        }
        
    }

    public function verDetallePedido(){
        if ( $_SERVER["REQUEST_METHOD"] === "POST"){
            $recordID = $_POST["record_id"];
            $data = $this->venta->getDetallePedido(intval($recordID));
            if ( $data != null ){
                echo json_encode(["success"=>true, "detalle"=>$data]);
            }
            else{
                echo json_encode(["success"=>false]);
            }
        }
    }

    public function realizarPedido() : void {
        $data["titulo"] = "Realizar pedido";
        $data["dni"] = $this->clientes->clientesDNI();
        $data["contenido"] = "views/venta/realizar_pedido.php";
        require_once TEMPLATE;
    }

    public function pagarPedido() : void {
        if ($_SERVER["REQUEST_METHOD"] === "POST" ){
            $record_id = $_POST["record_id"];
            $data["titulo"] = "Pagar venta";
            $data["comprobante"] = $this->venta->getComprobante();
            $data["tipoPago"] = $this->venta->getPago();
            $data["contenido"] = "views/venta/pagar_pedido.php";
            require_once TEMPLATE;
        }
    }

    public function metodoPagarPedido(): void{
        if ($_SERVER["REQUEST_METHOD"] === "POST"){
            $id_pedido = isset($_POST["id_pedido"]) ? $_POST["id_pedido"] : '';
            $montoPagado = isset($_POST["monto_pagado"]) ? $_POST["monto_pagado"] : '';
            $data = array(
                'id_venta' => $id_pedido,
                'monto_pagado' => $montoPagado
            );
            $exitoso = $this->venta->pay($data);
            if ( $exitoso == TRUE ){
                $ventaInsertar = $this->venta->getPedidoID($id_pedido);

                
                $exitoso = $this->venta->saveSale($data);

                echo json_encode(["success"=>true,"mensaje"=>"Pedido insertado en venta"]);
            }
            else{
                echo json_encode(["success"=>false,"mensaje"=>"Error al pagar"]);
            }
        }
    }
    
    public function verPedidosCajero() : void {
        $data["titulo"]= "Pedidos a pagar";
        $data["pedido"] = $this->venta->getPedido();
        $data["contenido"] = "views/venta/pedido_cajero.php";
        require_once TEMPLATE;
    }

    public function verVentasAdministrador() : void {
        $data["titulo"] = "Reportes de ventas - Administrador";
        $data["resultado"] = $this->venta->getVentas();
        $data["contenido"] = "views/venta/venta_administrador.php";
        require_once TEMPLATE;
    }
    public function agregarPedido() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            try{
                $pedido = json_decode($_POST["valores_pedido"]);
                $detalle_pedido = json_decode($_POST["valores_detalle_pedido"]);
                $columnas_detalle = ["id_plato", "id_categoria", "nombre", "cantidad", "precio"];
                $detalle_pedido_agregar = array_map(function($fila) use ($columnas_detalle) {
                    return array_combine($columnas_detalle, $fila);
                }, $detalle_pedido);

                $exitoso1 = $this->venta->saveOrder($pedido[0],$pedido[1],$pedido[2]);
                if ($exitoso1 == TRUE) {
                    $idVenta = $this->venta->maxPedido();
                    if ($idVenta > 0) {
                        foreach ($detalle_pedido_agregar as $detalle) {
                            $this->venta->saveOrderDetail($idVenta, $detalle);
                        }
                        echo json_encode(["success" => true, "mensaje" => "El pedido se ha registrado correctamente"]);
                    } else {
                        echo json_encode(["success" => false, "mensaje" => "Hubo un error con el id de pedido"]);
                    }
                } else {
                    echo json_encode(["success" => false, "mensaje" => "No se pudo realizar la operacion"]);
                }
            }
            catch ( Exception $e ){
                echo json_encode(["success"=>false,"mensaje"=>$e->getMessage()]);
            }
        }
    }

    public function obtenerReporteTotalProductos(){
        $productosObtenidos = $this->venta->reportProducts();
        if ( isset($productosObtenidos)){
            echo json_encode(["success"=>true, "productos"=>$productosObtenidos]);
        }
        else{
            echo json_encode(["success"=>false, "mensaje"=>"No hay productos"]);
        }
    }
    public function generarTicketOrden($id){
        $datos_pedido = $this->venta->getPedidoID($id);
        $datosDetallePedido = $this->venta->getDetallePedido($id);
        $nombreEmpresa = "D' LOLA RESTAURANTE CIX ©";
        $numeroPedido = $id;
        if ( empty($datos_pedido[0]["NombreApellidoCliente"])){
            $cliente = $datos_pedido[0]["TipoCliente"];
        }
        else{
            $cliente = $datos_pedido[0]["NombreApellidoCliente"];
        }
        $igv = 0.18;
        $total = 0;
        $this->pdf->SetMargins(4,10,4);
        $this->pdf->AddPage();
        $this->pdf->SetFont('Arial','B',10);
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1", strtoupper($nombreEmpresa)),0,'C',false);
        $this->pdf->SetFont('Arial','',9);
        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1","RUC: 1729278258"),0,'C',false);
        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1","Direccion: C. Teodoro Cárdenas 133, Lima 15046"),0,'C',false);
        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1","Teléfono: 955222600"),0,'C',false);
        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1","Email: DLOLARESTAURANTE@DLOLA.COM"),0,'C',false);
        $this->pdf->SetFont('Arial','',9);
        $this->pdf->Ln(1);
        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1", "ORDEN N°: " . $numeroPedido),0,'C',false);
        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1", "Cliente: " . $cliente),0,'C',false);
        $this->pdf->Ln(1);

        $this->pdf->Ln(1);
        $this->pdf->Cell(0,5,iconv("UTF-8", "ISO-8859-1","------------------------------------------------------"),0,0,'C');
        $this->pdf->Ln(5);
        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1","Fecha: ".$datos_pedido[0]["Fecha"]),0,'C',false);
        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1","Pedido Nro: ".$id),0,'C',false);
        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1","Cajero: ".$datos_pedido[0]["NombreApellidoMozo"]),0,'C',false);
        $this->pdf->Ln(1);
        $this->pdf->Ln(1);
        $this->pdf->SetFont('Arial','B',10);
        $this->pdf->SetFont('Arial','',9);
        # Tabla de productos #
        $this->pdf->Ln(1);
        $this->pdf->Cell(0,5,iconv("UTF-8", "ISO-8859-1","-------------------------------------------------------------------"),0,0,'C');
        $this->pdf->Ln(3);
        $this->pdf->Cell(10,5,iconv("UTF-8", "ISO-8859-1","Cant."),0,0,'C');
        $this->pdf->Cell(19,5,iconv("UTF-8", "ISO-8859-1","Precio"),0,0,'C');
        $this->pdf->Cell(15,5,iconv("UTF-8", "ISO-8859-1","Nombre"),0,0,'C');
        $this->pdf->Cell(28,5,iconv("UTF-8", "ISO-8859-1","Precio Final"),0,0,'C');
        $this->pdf->Ln(3);
        $this->pdf->Cell(72,5,iconv("UTF-8", "ISO-8859-1","-------------------------------------------------------------------"),0,0,'C');
        $this->pdf->Ln(5);
        /*----------  Detalles de la tabla  ----------*/
        foreach ($datosDetallePedido as $detallePedido){
            $this->pdf->Cell(10,4,iconv("UTF-8", "ISO-8859-1",$detallePedido["Cantidad"]),0,0,'C');
            $this->pdf->Cell(19,4,iconv("UTF-8", "ISO-8859-1",$detallePedido["Precio"]),0,0,'C');
            $this->pdf->Cell(19,4,iconv("UTF-8", "ISO-8859-1",$detallePedido["NombrePlato"]),0,0,'C');
            $this->pdf->Cell(28,4,iconv("UTF-8", "ISO-8859-1",$detallePedido["PrecioFinal"]),0,0,'C');
            $this->pdf->Ln(4);
            $total += $detallePedido["PrecioFinal"];
        }            
        $this->pdf->Ln(7);
        /*----------  Fin Detalles de la tabla  ----------*/
        $this->pdf->Cell(72,5,iconv("UTF-8", "ISO-8859-1","-------------------------------------------------------------------"),0,0,'C');
        $this->pdf->Ln(5);
        # Impuestos & totales #
        $this->pdf->Cell(18,5,iconv("UTF-8", "ISO-8859-1",""),0,0,'C');
        $this->pdf->Cell(22,5,iconv("UTF-8", "ISO-8859-1","OP.GRAVADAS: "),0,0,'C');
        $this->pdf->Cell(32,5,iconv("UTF-8", "ISO-8859-1",strval(floatval($total - ($total*$igv)))),0,0,'C');
        $this->pdf->Ln(5);

        $this->pdf->Cell(18,5,iconv("UTF-8", "ISO-8859-1",""),0,0,'C');
        $this->pdf->Cell(22,5,iconv("UTF-8", "ISO-8859-1","IGV (18%)"),0,0,'C');
        $this->pdf->Cell(32,5,iconv("UTF-8", "ISO-8859-1",strval(floatval($total*$igv))),0,0,'C');
        $this->pdf->Ln(5);

        $this->pdf->Cell(18,5,iconv("UTF-8", "ISO-8859-1",""),0,0,'C');
        $this->pdf->Cell(22,5,iconv("UTF-8", "ISO-8859-1","SUBTOTAL: "),0,0,'C');
        $this->pdf->Cell(32,5,iconv("UTF-8", "ISO-8859-1",strval(floatval($total))),0,0,'C');
        $this->pdf->Ln(5);
        
        $this->pdf->Cell(18,5,iconv("UTF-8", "ISO-8859-1",""),0,0,'C');
        $this->pdf->Cell(70,20,iconv("UTF-8", "ISO-8859-1","Total a cancelar: S/.".strval(floatval($total))),0,0,'C');

        $this->pdf->SetXY(0,$this->pdf->GetY()+21);
        $this->pdf->SetFont('Arial','',14);
        # Nombre del archivo PDF #
        $this->pdf->Output("I","Orden_Nro_".$id.".pdf",true);
    }

    public function generarTicketVenta($id) {
        $datos_venta = $this->venta->getVentaID($id);
        $datosDetalleVenta = $this->venta->getDetalleVenta($id);
        $nombre_empresa = "D' Lola Restaurante ©";
        $numero_venta = $id;
        $cliente = $datos_venta["cliente"];
        $total = 0;
        foreach($datosDetalleVenta as $detalleArray) {
            $total += $detalleArray["iDetCantidad"] * $detalleArray["cPlaPrecio"];
        }
        $total_pagado = $datos_venta["fPedTotal"];
        $codigo_barras = "COD00000V000".strval($id);
        $this->pdf->SetMargins(4,10,4);
        $this->pdf->AddPage();
        $this->pdf->SetFont('Arial','B',10);
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1", strtoupper($nombre_empresa)),0,'C',false);
        $this->pdf->SetFont('Arial','',9);
        $this->pdf->Ln(1);
        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1", "TICKET - VENTA N°: " . $numero_venta),0,'C',false);
        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1", "Cliente: " . $cliente),0,'C',false);
        $this->pdf->Ln(1);


        $this->pdf->Ln(1);
        $this->pdf->Cell(0,5,iconv("UTF-8", "ISO-8859-1","------------------------------------------------------"),0,0,'C');
        $this->pdf->Ln(5);

        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1","Fecha: ".date("d/m/Y")),0,'C',false);
        //$this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1","Caja Nro: 1"),0,'C',false);
        //$this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1","Cajero: Carlos Alfaro"),0,'C',false);
        $this->pdf->Ln(1);
        $this->pdf->Ln(1);
        $this->pdf->SetFont('Arial','B',10);
        $this->pdf->SetFont('Arial','',9);

            # Tabla de productos #
        $this->pdf->Cell(10,5,iconv("UTF-8", "ISO-8859-1","Cant."),0,0,'C');
        $this->pdf->Cell(19,5,iconv("UTF-8", "ISO-8859-1","Precio"),0,0,'C');
        $this->pdf->Cell(15,5,iconv("UTF-8", "ISO-8859-1","Desc."),0,0,'C');
        $this->pdf->Cell(28,5,iconv("UTF-8", "ISO-8859-1","Total"),0,0,'C');
        $this->pdf->Ln(3);
        $this->pdf->Cell(72,5,iconv("UTF-8", "ISO-8859-1","-------------------------------------------------------------------"),0,0,'C');
        $this->pdf->Ln(3);
    /*----------  Detalles de la tabla  ----------*/
        $this->pdf->MultiCell(0,4,iconv("UTF-8", "ISO-8859-1","Nombre de producto a vender"),0,'C',false);
        $this->pdf->Cell(10,4,iconv("UTF-8", "ISO-8859-1","7"),0,0,'C');
        $this->pdf->Cell(19,4,iconv("UTF-8", "ISO-8859-1","$10 USD"),0,0,'C');
        $this->pdf->Cell(19,4,iconv("UTF-8", "ISO-8859-1","$0.00 USD"),0,0,'C');
        $this->pdf->Cell(28,4,iconv("UTF-8", "ISO-8859-1","$70.00 USD"),0,0,'C');
        $this->pdf->Ln(4);
        $this->pdf->MultiCell(0,4,iconv("UTF-8", "ISO-8859-1","Garantía de fábrica: 2 Meses"),0,'C',false);
        $this->pdf->Ln(7);
    /*----------  Fin Detalles de la tabla  ----------*/

        // Mostrar el total pagado y el cambio
        $cambio = $total_pagado - $total;
        $this->pdf->Cell(0,5,iconv("UTF-8", "ISO-8859-1","TOTAL PAGADO"),0,0,'C');
        $this->pdf->Cell(32,5,iconv("UTF-8", "ISO-8859-1",'$'.number_format($total_pagado,2)),0,0,'C');
        $this->pdf->Ln(5);
        $this->pdf->Cell(0,5,iconv("UTF-8", "ISO-8859-1","CAMBIO"),0,0,'C');
        $this->pdf->Cell(32,5,iconv("UTF-8", "ISO-8859-1",'$'.number_format($cambio,2)),0,0,'C');
        $this->pdf->Ln(5);
    
        // Más código ...
    
        # Codigo de barras #
        $this->pdf->Code128(5,$this->pdf->GetY(),$codigo_barras,70,20);
        $this->pdf->SetXY(0,$this->pdf->GetY()+21);
        $this->pdf->SetFont('Arial','',14);
        $this->pdf->MultiCell(0,5,iconv("UTF-8", "ISO-8859-1",$codigo_barras),0,'C',false);
        
        # Nombre del archivo PDF #
        $this->pdf->Output("I","Ticket_Nro_".$id.".pdf",true);
    }   
    

    private function showError404() : void {
        if (defined('ERROR404')) {
            require_once ERROR404;
        } else {
            echo "Error 404: Página no encontrada";
        }
    }
}