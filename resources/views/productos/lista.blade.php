<style type="text/css">
	td img{
		width: 100px;
	}
	td{
		vertical-align: middle;
	}
	#filtros{
		cursor: pointer;
	}
	#filtros span{
		transition: all ease-in 0.3s;
		display: inline-block;
	}
	#filtros.abierto span{
		transform: rotate(180deg);
	}
	#panelFiltros{
		overflow: hidden;
		height: 0;
		transition: all ease-in 0.3s;
	}
	.ordenable{
		cursor: pointer;
		white-space: nowrap;
	}
	.ordenable::after{
		content: ' -';
	}
	.ordenable.asc::after{
		content: ' ▲';
	}
	.ordenable.desc::after{
		content: ' ▼';
	}
</style>
<x-layout>

	<main class="container-lg pt-3">
		<h3 id="filtros" class="abierto">Filtros <span>▼</span></h3>
		<div id="panelFiltros" style="height:auto">
			<div class="row">
				<div class="col-12 col-xl-3 col-lg-2 col-md-3 col-sm-4 mb-2">
					<input type="text" class="form-control" placeholder="Nombre" id="filtroNombre">
				</div>
				<div class="col-12 col-xl-2 col-lg-2 col-md-3 col-sm-4 mb-2">
					<select class="form-select" id="filtroMarca">
						<option value="">Marca</option>
						@foreach($marcas as $marca)
							<option>{{$marca->nombre}}</option>
						@endforeach
					</select>
				</div>
				<div class="col-12 col-xl-2 col-lg-2 col-md-3 col-sm-4 mb-2">
					<select class="form-select" id="filtroCategoria">
						<option value="">Categoría</option>
						@foreach($categorias as $categoria)
              <option value="1{{$categoria['nombre']}}" style="font-weight:bold">{{$categoria['nombre']}}</option>
              @foreach($categoria['subcategorias'] as $subcategoria)
                <option value="2{{$subcategoria['nombre']}}">{{$subcategoria['nombre']}}</option>
                @foreach($subcategoria['subsubcategorias'] as $subsubcategoria)
                  <option value="3{{$subsubcategoria}}">- {{$subsubcategoria}}</option>
                @endforeach
              @endforeach
            @endforeach
					</select>
				</div>
				<div class="col-12 col-xl-2 col-lg-2 col-md-3 col-sm-4 mb-2">
					<input type="text" class="form-control" placeholder="Código" id="filtroCodigo">
				</div>
				<div class="col-12 col-xl-2 col-lg-2 col-md-3 col-sm-4 mb-2">
					<select class="form-select" id="filtroEstado">
						<option value="1">Activos</option>
						<option value="-1">Inactivos</option>
						<option value="2">Todos</option>
					</select>
				</div>
				<div class="col-12 col-xl-1 col-lg-2 col-md-3 col-sm-4 mb-2">
					<button class="btn btn-primary" onclick="limpiarFiltros()">Limpiar</button>
				</div>
			</div>
		</div>
		<div class="mt-3">
			<button class="btn btn-primary" onclick="abrirModalDescuentoMasivo()">Aplicar descuento a listados</button>
			<a href="{{route('crear-producto')}}" class="btn btn-success">Nuevo Producto</a>
		</div>
		<div class="table-responsive mt-5">
			<table class="table table-hover">
				<thead>
				    <tr>
				      <th scope="col" class="ordenable" data-campo="codigo">Código</th>
				      <th scope="col">Foto</th>
				      <th scope="col" class="ordenable" data-campo="nombre">Nombre</th>
				      <th scope="col" class="ordenable" data-campo="precio">Precio</th>
				      <th scope="col" class="ordenable" data-campo="tieneDescuento">Descuento</th>
				      <th scope="col" class="ordenable" data-campo="categoria_visible">Categoría</th>
				      <th scope="col" class="ordenable" data-campo="marca">Marca</th>
				      <th scope="col" class="ordenable" data-campo="visitas">Visitas</th>
				      <th scope="col" class="ordenable" data-campo="estado">Activo</th>
				      <th scope="col">Acciones</th>
				    </tr>
				  </thead>
				  <tbody id="tabla">
				    
				  </tbody>
			</table>
		</div>
	</main>
	<div class="modal fade" tabindex="-1" id="modalDescuento">
      <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
            	<div class="alert alert-danger" style="display:none" id="alertaDescuento"></div>
              <div class="mb-3">
                <label for="descuento" class="form-label">Descuento</label>
                <div class="row">
                	<div class="col-9">
                		<input type="number" class="form-control" id="descuento" placeholder="Cantidad" step="0.01">
                	</div>
                	<div class="col-3">
                		<select id="tipoDescuento" class="form-select col-2"><option value="por">%</option><option value="pre">€</option></select>
                	</div>
                </div>
              </div>
              <div class="mb-3">
                <label for="fecha" class="form-label">Fecha fin</label>
                <input type="date" class="form-control" name="fecha" id="fecha">
                <div class="form-text">Fecha de fin (incluida). Sin fecha, el descuento será permanente hasta que se cancele manualmente.</div>
            	</div>
            	<h4>Precio final: <span id="precioFinal">XXX</span></h4>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" id="botonDescuento" class="btn btn-primary" disabled>Aplicar</button>
            </div>
        </div>
      </div>
  </div>

  <div class="modal fade" tabindex="-1" id="modalDescuentoMasivo">
      <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
            	<div class="alert alert-danger" style="display:none" id="alertaDescuentoMasivo"></div>
              <div class="mb-3">
                <label for="descuentoMasivo" class="form-label">Descuento</label>
                <div class="input-group">
              		<input type="number" class="form-control" id="descuentoMasivo" placeholder="Cantidad" step="1">
              		<span class="input-group-text">%</span>
              	</div>
              </div>
              <div class="mb-3">
                <label for="fechaMasiva" class="form-label">Fecha fin</label>
                <input type="date" class="form-control" name="fechaMasiva" id="fechaMasiva">
                <div class="form-text">Fecha de fin (incluida). Sin fecha, el descuento será permanente hasta que se cancele manualmente.</div>
            	</div>
            	<table class="table table-striped">
            		<thead>
            			<th>Producto</th>
            			<th>Precio actual</th>
            			<th>Precio final</th>
            		</thead>
            		<tbody id="tablaDescuentos">
            			
            		</tbody>
            	</table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" id="botonDescuentoMasivo" class="btn btn-primary" disabled>Aplicar</button>
            </div>
        </div>
      </div>
  </div>
</x-layout>
<script type="text/javascript">
	//Cargar datos
		let datos = [
			@foreach($referencias as $referencia)
				{
					"id":{{$referencia->id}},
					"codigo":"{{$referencia->codigo}}",
					"foto":"@if(count($referencia->producto->fotos)){{$referencia->producto->fotos[0]->direccion}}@endif",
					"nombre":"{{$referencia->producto->nombre}}",
					"precio":{{$referencia->precio}},
					"descuento":@if($referencia->descuento){{$referencia->descuento}}@else""@endif,
					"tieneDescuento":@if($referencia->descuento){{1}}@else{{0}}@endif,
					"finDescuento":"{{$referencia->fin_descuento}}",
					"categoria":"@if($referencia->producto->categoria->categoria_id && $referencia->producto->categoria->categoria->categoria_id){{
						$referencia->producto->categoria->categoria->categoria->nombre
					}}@elseif($referencia->producto->categoria->categoria_id && !$referencia->producto->categoria->categoria->categoria_id){{$referencia->producto->categoria->categoria->nombre}}@else{{$referencia->producto->categoria->nombre}}@endif",
					"subcategoria":"@if($referencia->producto->categoria->categoria_id && $referencia->producto->categoria->categoria->categoria_id){{
						$referencia->producto->categoria->categoria->nombre
					}}@elseif($referencia->producto->categoria->categoria_id && !$referencia->producto->categoria->categoria->categoria_id){{$referencia->producto->categoria->nombre}}@endif",
					"subsubcategoria":"@if($referencia->producto->categoria->categoria_id && $referencia->producto->categoria->categoria->categoria_id){{
						$referencia->producto->categoria->nombre}}@endif",
					"categoria_visible":"{{$referencia->producto->categoria->nombre}}",
					"marca":"{{$referencia->producto->marca->nombre}}",
					"estado":@if(!$referencia->producto->confirmado){{-1}}@elseif($referencia->disponible){{1}}@else{{0}}@endif,
					"visitas":{{$referencia->visitas}}},
			@endforeach
		];
		let datosVisibles = [];
	
	//Mostrar/ocultar filtros
		filtros.onclick = ()=>{
			let altura = panelFiltros.children[0].offsetHeight;
			if(filtros.classList.contains('abierto')){
				filtros.classList.remove('abierto');
				panelFiltros.style.height = 0;
			}else{
				filtros.classList.add('abierto');
				panelFiltros.classList.add('abierto');
				panelFiltros.style.height = altura+'px';
			}
		}
	
	//Aplicar filtros
		filtroNombre.oninput = pintarTabla;
		filtroMarca.onchange = pintarTabla;
		filtroCategoria.onchange = pintarTabla;
		filtroCodigo.oninput = pintarTabla;
		filtroEstado.onchange = pintarTabla;

		function aplicarFiltros(){
			datosVisibles = [];
			for(let producto of datos){
				let visible = true;
				//Filtro de nombre
				if(filtroNombre.value && !producto.nombre.includes(filtroNombre.value)){
					visible = false;
				}
				//Filtro de marca
				else if(filtroMarca.value && producto.marca!=filtroMarca.value){
					visible = false;
				}
				//Filtro de categoría
				else if(filtroCategoria.value){
					let tipo = filtroCategoria.value.substr(0,1);
					let valor = filtroCategoria.value.substr(1);
					if(tipo==1 && valor!=producto.categoria){
						visible = false;
					}
					else if(tipo==2 && valor!=producto.subcategoria){
						visible = false;
					}
					else if(tipo==3 && valor!=producto.subsubcategoria){
						visible = false;
					}
				}
				//Filtro de código
				else if(filtroCodigo.value && !producto.codigo.includes(filtroCodigo.value)){
					visible = false;
				}
				//Filtro de estado
				else if((filtroEstado.value=='1'&&producto.estado!=1) || (filtroEstado.value=='-1'&&producto.estado==1)){
					visible = false;
				}

				if(visible){
					datosVisibles.push(producto);
				}
			}
		}

		function limpiarFiltros(){
			filtroNombre.value="";
			filtroMarca.value="";
			filtroCategoria.value="";
			filtroCodigo.value="";
			filtroEstado.value="1";
			pintarTabla();
		}
	
	//Ordenar tabla

		var campoOrdenar, direccionOrdenar;

		for(let header of document.getElementsByClassName('ordenable')){
			header.onclick = ordenarTabla;
		}

		function ordenarTabla(evento){
			direccionOrdenar = 1;
			//Si está descendente, ponerlo ascendente
			if(evento.target.classList.contains('desc')){
				evento.target.classList.remove('desc');
				evento.target.classList.add('asc');
				direccionOrdenar = -1;
				campoOrdenar = evento.target.dataset.campo;
			}
			//Si está ascendente, quitar el orden
			else if(evento.target.classList.contains('asc')){
				evento.target.classList.remove('asc');
				campoOrdenar = 'id';
			}
			//Si está sin ordenar, ponerlo descendente
			else{
				//Quitar las clases de las demás
				for(let header of document.getElementsByClassName('ordenable')){
					header.classList.remove('asc');
					header.classList.remove('desc');
				}
				evento.target.classList.add('desc');
				campoOrdenar = evento.target.dataset.campo;
			}
			
			datos.sort(comparador);
			pintarTabla();
		}

		function comparador( el1, el2 ) {
			let resultado = 0;
			let dato1 = el1[campoOrdenar];
			let dato2 = el2[campoOrdenar];
			if(typeof dato1 == "string"){
				dato1 = dato1.toUpperCase();
				dato2 = dato2.toUpperCase();
			}
		  if ( dato1 < dato2 ){
		    resultado = -1 * direccionOrdenar;
		  }
		  else if ( dato1 > dato2 ){
		    resultado = 1 * direccionOrdenar;
		  }
		  return resultado;
		}

	//Pintar datos
		function pintarTabla(){
			aplicarFiltros();
			tabla.innerHTML = "";
			let indice = 0;
			for(let producto of datosVisibles){
				let fila = document.createElement("tr");
				if(producto.estado == 1){
					fila.classList.add("table-success");
				}else if(producto.estado==-1){
					fila.classList.add("table-dark");
				}else{
					fila.classList.add("table-secondary");
				}
				
				let celdaCodigo = document.createElement("td");
				celdaCodigo.innerText = producto.codigo;
				fila.append(celdaCodigo);

				let celdaFoto = document.createElement("td");
				if(producto.foto){
					let foto = document.createElement("img");
					foto.classList.add("img-thumbnail");
					foto.src = "/storage/"+producto.foto;
					celdaFoto.append(foto);
				}
				fila.append(celdaFoto);

				let celdaNombre = document.createElement("td");
				celdaNombre.innerText = producto.nombre;
	      fila.append(celdaNombre);

	      let celdaPrecio = document.createElement("td");
	      if(producto.descuento){
	      	let original = document.createElement("s");
	      	original.innerText = producto.precio;
	      	celdaPrecio.append(original);
	      	celdaPrecio.append(" "+(producto.precio-producto.descuento)+" €");
	      	if(producto.finDescuento){
	      		let salto = document.createElement("br");
	      		celdaPrecio.append(salto);
	      		celdaPrecio.append("Hasta "+producto.finDescuento);
	      	}
	      }else{
	      	celdaPrecio.innerText = producto.precio+" €";
	      }
	      fila.append(celdaPrecio);

	      let celdaDescuento = document.createElement("td");
	      let botonDescuento = document.createElement("button");
	      botonDescuento.classList.add("btn");
	      if(producto.descuento){
	      	botonDescuento.classList.add("btn-danger");
	      	botonDescuento.innerText = "Quitar";
	      	botonDescuento.onclick = ()=>{quitarDescuento(producto.id)};
	      }else{
	      	botonDescuento.classList.add("btn-primary");
	      	botonDescuento.innerText = "Aplicar";
	      	botonDescuento.dataset.indice = indice;
	      	botonDescuento.onclick = abrirModalDescuento;
	      }
	      if(producto.estado == 1){
	      	celdaDescuento.append(botonDescuento);
	      }
	      fila.append(celdaDescuento);

	      let celdaCategoria = document.createElement("td");
	      celdaCategoria.innerText = producto.categoria_visible;
	      fila.append(celdaCategoria);

	      let celdaMarca = document.createElement("td");
	      celdaMarca.innerText = producto.marca;
	      fila.append(celdaMarca);

	      let celdaVisitas = document.createElement("td");
	      celdaVisitas.innerText = producto.visitas;
	      fila.append(celdaVisitas);

	      let celdaEstado = document.createElement("td");
	      let checkEstado = document.createElement("input");
	      checkEstado.type = "checkbox";
	      checkEstado.classList.add("form-check-control");
	      checkEstado.dataset.indice = indice;
	      checkEstado.onclick = cambiarEstado;
	      if(producto.estado == 1){
					checkEstado.checked = true;
					celdaEstado.append(checkEstado);				
				}
				else if(producto.estado==-1){
					let badge = document.createElement("span");
					badge.classList.add("badge");
					badge.classList.add("bg-warning");
					badge.innerText = "Pendiente aprobación";
					celdaEstado.append(badge);
				}
				else{
					celdaEstado.append(checkEstado);				
				}
				fila.append(celdaEstado);

				let celdaAccion = document.createElement("td");
				let botonEditar = document.createElement("a");
				botonEditar.classList.add("btn");
				botonEditar.classList.add("btn-secondary");
				botonEditar.innerText = "Editar";
				botonEditar.href = '{{route('editar-producto',["id"=>"fake"])}}'.replace("fake",producto.id);


				celdaAccion.append(botonEditar);
				fila.append(celdaAccion);

				tabla.append(fila);
				indice++;
			}
		}

		pintarTabla();

	//Aplicar descuentos individuales
		modalDescuento = new bootstrap.Modal(modalDescuento);

		var productoSeleccionado;
		var valorPrecioFinal;
		var precioDescuento;
		function abrirModalDescuento(evento){
			productoSeleccionado = datosVisibles[evento.target.dataset.indice];
			valorPrecioFinal = productoSeleccionado.precio;
			precioDescuento = 0;
			descuento.value = "";
			tipoDescuento.value = "por";
			fecha.value = "";
			precioFinal.parentElement.style.display = "block";
			precioFinal.innerText = valorPrecioFinal + " €";
			modalDescuento.toggle();
		}

		descuento.oninput = calcularDescuento;
		tipoDescuento.onchange = calcularDescuento;
		botonDescuento.onclick = aplicarDescuento;

		function calcularDescuento(){
			precioDescuento = descuento.value;
			if(tipoDescuento.value == "por"){
				precioDescuento = productoSeleccionado.precio*precioDescuento/100;
			}
			valorPrecioFinal = productoSeleccionado.precio - precioDescuento;
			valorPrecioFinal = valorPrecioFinal.toFixed(2);
			precioFinal.innerText = valorPrecioFinal + " €";
			if(valorPrecioFinal<=0 || precioDescuento==0){
				botonDescuento.disabled = true;
			}else{
				botonDescuento.disabled = false;
			}
		}

		function aplicarDescuento(){
			botonDescuento.disabled = true;
			//Enviar los datos
			var formData = new FormData();
	    formData.append('id', productoSeleccionado.id);
	    formData.append('descuento', precioDescuento);
	    if(fecha.value){
	      formData.append('fecha', fecha.value);
	    }
			postData('{{route('aplicar-descuento')}}',formData).then((data)=>{
	      botonDescuento.disabled = false;
	      //Si es OK, cerrar el modal y borrar los datos
	      if(data.resultado){
	        //Actualizar el producto seleccionado en la tabla
	        for(let i = 0; i < datos.length; i++){
	        	if(datos[i].id == productoSeleccionado.id){
	        		datos[i].descuento = precioDescuento;
	        		datos[i].tieneDescuento = 1;
	        		datos[i].finDescuento = fecha.value;
	        		break;
	        	}
	        }
	        //Repintar la tabla
	        pintarTabla();
	        modalDescuento.toggle();
	      }
	      //Si no, pintar alert de error
	      else{
	        alertaDescuento.style.display = "block";
	        alertaDescuento.innerHTML = data.mensaje;
	      }

	    });
		}

	//Quitar descuento

	  function quitarDescuento(id){
	  	if(confirm("¿Quitar el descuento?")){
	  		var formData = new FormData();
		    formData.append('id', id);
	  		postData('{{route('quitar-descuento')}}',formData).then((data)=>{
		      //Si es OK
		      if(data.resultado){
		        //Actualizar el producto seleccionado en la tabla
		        for(let i = 0; i < datos.length; i++){
		        	if(datos[i].id == id){
		        		datos[i].descuento = "";
		        		datos[i].tieneDescuento = 0;
		        		datos[i].finDescuento = "";
		        		break;
		        	}
		        }
		        //Repintar la tabla
		        pintarTabla();
		      }
		      //Si no, pintar alert de error
		      else{
		        alert(data.mensaje);
		      }
		    });
	  	}
	  }

	//Aplicar descuentos masivos
		let productosDescuento = [];
		modalDescuentoMasivo = new bootstrap.Modal(modalDescuentoMasivo);
		descuentoMasivo.oninput = calcularDescuentoMasivo;
		botonDescuentoMasivo.onclick = aplicarDescuentoMasivo;
		function abrirModalDescuentoMasivo(){
			//Seleccionar los datos a descontar
			productosDescuento = [];
			descuentoMasivo.value = "";
			fechaMasiva.value = "";
			alertaDescuentoMasivo.style.display = "none";
			for(let producto of datosVisibles){
				if(producto.estado==1 && !producto.tieneDescuento){
					let productoDescuento = {"id":producto.id,"nombre":producto.nombre,"precio":producto.precio,"descuento":0};
					productosDescuento.push(productoDescuento);
				}
			}		
			if(productosDescuento.length>0){
				//Pintar la tabla
				pintarTablaDescuentos();
				//Abrir el modal
				modalDescuentoMasivo.toggle();
			}
		}
		function pintarTablaDescuentos(){
			tablaDescuentos.innerHTML = "";
			for(let producto of productosDescuento){
				let fila = document.createElement("tr");
				tablaDescuentos.append(fila);

				let celdaNombre = document.createElement("td");
				celdaNombre.innerText=producto.nombre;
				fila.append(celdaNombre);

				let celdaPrecio = document.createElement("td");
				celdaPrecio.innerText = producto.precio+" €";
				fila.append(celdaPrecio);

				let celdaDescuento = document.createElement("td");
				celdaDescuento.innerText = (producto.precio - producto.descuento)+" €";
				fila.append(celdaDescuento);

			}
		}
		function calcularDescuentoMasivo(){
			if(descuentoMasivo.value){
				for(let i = 0;i<productosDescuento.length;i++){
					productosDescuento[i].descuento = (productosDescuento[i].precio*descuentoMasivo.value/100).toFixed(2);
				}
				botonDescuentoMasivo.disabled = false;
				pintarTablaDescuentos();
			}else{
				botonDescuentoMasivo.disabled = true;
			}
		}
		function aplicarDescuentoMasivo(){
			botonDescuentoMasivo.disabled = true;
			//Enviar los datos
			var formData = new FormData();
			let ids = [];
			for(let producto of productosDescuento){
	    	formData.append('ids[]', producto.id);
	    	ids.push(producto.id);
			}
	    formData.append('descuento', descuentoMasivo.value);
	    if(fechaMasiva.value){
	      formData.append('fecha', fechaMasiva.value);
	    }
			postData('{{route('aplicar-descuento-masivo')}}',formData).then((data)=>{
	      botonDescuentoMasivo.disabled = false;
	      //Si es OK, cerrar el modal y borrar los datos
	      if(data.resultado){
	        //Actualizar el producto seleccionado en la tabla
	        for(let i = 0; i < datos.length; i++){
	        	if(ids.includes(datos[i].id)){
	        		datos[i].descuento = datos[i].precio*descuentoMasivo.value/100;
	        		datos[i].tieneDescuento = 1;
	        		if(fechaMasiva.value){
	        			datos[i].finDescuento = fechaMasiva.value;
        			}
	        	}
	        }
	        //Repintar la tabla
	        pintarTabla();
	        botonDescuentoMasivo.disabled = true;
	        modalDescuentoMasivo.toggle();
	      }
	      //Si no, pintar alert de error
	      else{
	        alertaDescuentoMasivo.style.display = "block";
	        alertaDescuentoMasivo.innerHTML = data.mensaje;
	      }

	    });
		}

	//Activar/Desactivar producto

		function cambiarEstado(evento){
			var formData = new FormData();
	    formData.append('id', datosVisibles[evento.target.dataset.indice].id);
  		postData('{{route('cambiar-estado')}}',formData).then((data)=>{
	      //Si es OK
	      if(data.resultado){
	        //Actualizar el producto seleccionado en la tabla
	        for(let i = 0; i < datos.length; i++){
	        	if(datos[i].id == datosVisibles[evento.target.dataset.indice].id){
	        		datos[i].estado = data.mensaje;
	        		break;
	        	}
	        }
	        //Repintar la tabla
	        pintarTabla();
	      }
	      //Si no, pintar alert de error
	      else{
	        alert(data.mensaje);
	      }
	    });

		}

	//Función POST AJAX
		async function postData(ruta,datos){
	    //Leer los datos
	    datos.append('_token', '{{csrf_token()}}');
	    //Enviar el post
	    const response = await fetch(ruta, {
	      method: "POST", // *GET, POST, PUT, DELETE, etc.
	      body: datos, // body data type must match "Content-Type" header
	    });
	    return response.json();
	  }
</script>