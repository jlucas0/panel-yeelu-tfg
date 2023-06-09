<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Referencia;
use App\Models\Marca;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Foto;
use App\Models\GrupoEtiqueta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ProductoController extends Controller
{
    //
    public function crear(){

        $marcas = Marca::where('tienda_id',null)->orWhere('tienda_id',Auth::id())->orderBy('nombre','asc')->get();
        
        $categorias = Categoria::with(['categorias','categorias.categorias'])->whereNull('categoria_id')->get();

        $productos = Producto::with('marca')->where('confirmado',1)->get();
        $etiquetas = GrupoEtiqueta::with('etiquetas')->get();
        return view('productos.crear',["marcas" => $marcas,"categorias" => $categorias,"productos"=>$productos,"etiquetas"=>$etiquetas]);
    }

    public function guardar(Request $request){
        
        $validaciones = [
            "codigo" => ['required','max:150',Rule::unique('referencias')->where(fn ($query) => $query->where('tienda_id', Auth::id()))],
            "precio" => ['required','min:0','numeric'],
            "maximo" => ['nullable','min:1','numeric'],
        ];
        $textos = [
            "codigo.required" => "Campo obligatorio",
            "codigo.max" => "Máximo 150 caracteres",
            "codigo.unique" => "Ya existe el código",
            "precio.min" => "El valor mínimo es 0",
            "precio.required" => "Campo obligatorio",
            "precio.numeric" => "Debe ser un número válido",
            "maximo.min" => "El valor mínimo es 1",
            "maximo.numeric" => "Debe ser un número válido" 
        ];

        //Si está creando solamente una referencia
        if($request->accion == 'buscar'){
            $validaciones["seleccionado"] = ['required','exists:productos,id'];
            $textos['seleccionado.required'] = "Debes elegir un producto existente";
            $textos['seleccionado.exists'] = "El producto seleccionado no está en la lista";
        }

        //Si es un producto nuevo
        else{
            $validaciones["nombre"] = ['required','max:200'];
            $textos["nombre.required"] = "Campo obligatorio";
            $textos["nombre.max"] = "Texto demasiado largo";
            $validaciones["categoria"] = ['required',Rule::exists('categorias','id')->where(function ($query) {
                return $query->whereNotNull('categoria_id');
            })];
            $textos["categoria.required"] = "Campo obligatorio";
            $textos["categoria.exists"] = "Elige una categoría válida";
            $validaciones["marca"] = ['required','exists:marcas,id'];
            $textos["marca.required"] = "Campo obligatorio";
            $textos["marca.exists"] = "Elige una categoría válida";
            $validaciones["fotos.*"] = ['nullable','image','max:4096'];
            $textos["fotos.*.image"] = "Alguna foto no tenía un formato válido";
            $textos["fotos.*.max"] = "Las fotos no pueden superar los 4MB cada una";
            $validaciones["peso"] = ['nullable','numeric','min:1'];
            $textos["peso.numeric"] = "No es un número válido";
            $textos["peso.min"] = "El mínimo es 1";
            $validaciones["kcal"] = ['nullable','numeric'];
            $textos["kcal.numeric"] = "No es un número válido";
            $validaciones["grasas"] = ['nullable','numeric'];
            $textos["grasas.numeric"] = "No es un número válido";
            $validaciones["saturadas"] = ['nullable','numeric'];
            $textos["saturadas.numeric"] = "No es un número válido";
            $validaciones["hidratos"] = ['nullable','numeric'];
            $textos["hidratos.numeric"] = "No es un número válido";
            $validaciones["azucar"] = ['nullable','numeric'];
            $textos["azucar.numeric"] = "No es un número válido";
            $validaciones["proteinas"] = ['nullable','numeric'];
            $textos["proteinas.numeric"] = "No es un número válido";
            $validaciones["sal"] = ['nullable','numeric'];
            $textos["sal.numeric"] = "No es un número válido";
            $validaciones["etiquetas.*"] = ['nullable','exists:etiquetas,id'];
            $textos["etiquetas.*.exists"] = "Alguna etiqueta no es válida";
        }

        $valido = $request->validate($validaciones,$textos);

        try{

            $productoId = null;

            if($request->accion == 'crear'){
                //Datos del producto
                $producto = new Producto();
                $producto->nombre = $valido["nombre"];
                if(isset($valido["peso"])){
                    $producto->peso = $valido["peso"];
                    $producto->unidad = $request->unidad;
                }
                $producto->descripcion = $request->descripcion;
                $producto->ingredientes = $request->ingredientes;
                if(isset($valido["kcal"])){
                    $producto->kcal = $valido["kcal"];
                }
                if(isset($valido["grasas"])){
                    $producto->grasas = $valido["grasas"];
                }
                if(isset($valido["saturadas"])){
                    $producto->saturadas = $valido["saturadas"];
                }
                if(isset($valido["hidratos"])){
                    $producto->hidratos = $valido["hidratos"];
                }
                if(isset($valido["azucar"])){
                    $producto->azucar = $valido["azucar"];
                }
                if(isset($valido["proteinas"])){
                    $producto->proteinas = $valido["proteinas"];
                }
                if(isset($valido["sal"])){
                    $producto->sal = $valido["sal"];
                }
                $producto->confirmado = false;
                $producto->marca_id = $valido["marca"];
                $producto->categoria_id = $valido["categoria"];
                $producto->save();
                $productoId = $producto->id;
                //Sus fotos
                if(isset($request->fotos)){
                    foreach($request->fotos as $i=>$foto){
                        $path = $foto->store('productos','public');
                        $fotoBd = new Foto();
                        if($i==$request->fotoPrincipal){
                            $fotoBd->principal = true;
                        }
                        $fotoBd->direccion = $path;
                        $fotoBd->producto_id = $productoId;
                        $fotoBd->save();
                    }
                }
                //Referencia a las etiquetas
                if(isset($request->etiquetas)){
                    foreach($request->etiquetas as $etiqueta){
                        $producto->etiquetas()->attach($etiqueta);
                    }
                }
            }
            else{
                $productoId = $valido['seleccionado'];
                //Verificar que no está ya en el catálogo
                if(Referencia::where('producto_id',$productoId)->where('tienda_id',Auth::id())->where('borrado',0)->first()){
                    return back()->withErrors(["seleccionado"=>"El producto ya está en tu catálogo"]);
                }
            }

            $referencia = new Referencia();
            $referencia->codigo = $valido['codigo'];
            $referencia->precio = $valido['precio'];
            if(isset($valido['maximo'])){
                $referencia->maximo_venta = $valido['maximo'];
            }
            $referencia->disponible = true;
            $referencia->producto_id = $productoId;
            $referencia->tienda_id = Auth::id();
            $referencia->save();
            return redirect('productos')->withErrors(['success' => "Producto registrado"]);
        }catch(\Exception $e){
            return back()->withErrors([
                'danger' => 'Se ha producido un error en el sistema.'.(config('app.env')!="production" ? " ".$e->getMessage():"")
            ]);
        }
    
    }

    public function editar($id){
        $referencia = Referencia::find($id);

        if(!$referencia || $referencia->tienda_id!=Auth::id()){
            return back()->withErrors(["warning"=>"Producto no encontrado"]);
        }

        return view('productos.editar',["referencia"=>$referencia]);
    }

    public function modificar(Request $request){
        $validaciones = [
            "id" => ['required','exists:referencias,id'],
            "codigo" => ['required','max:150',Rule::unique('referencias')->where(fn ($query) => $query->where('tienda_id', Auth::id())->where('id','<>',$request->id))],
            "precio" => ['required','min:0','numeric'],
            "maximo" => ['nullable','min:1','numeric'],
        ];
        $textos = [
            "id.required" => "Referencia no especificada",
            "id.exists" => "La referencia no existe",
            "codigo.required" => "Campo obligatorio",
            "codigo.max" => "Máximo 150 caracteres",
            "codigo.unique" => "Ya existe el código",
            "precio.min" => "El valor mínimo es 0",
            "precio.required" => "Campo obligatorio",
            "precio.numeric" => "Debe ser un número válido",
            "maximo.min" => "El valor mínimo es 1",
            "maximo.numeric" => "Debe ser un número válido" 
        ];

        $valido = $request->validate($validaciones,$textos);

        try{

            $referencia = Referencia::find($valido["id"]);
            //Validar también que se está intentando modificar un producto correcto
            if($referencia->tienda_id!=Auth::id()){
                return back()->withErrors(["warning"=>"Intento de modificación no permitido"]);
            }
            

            $referencia->codigo = $valido['codigo'];
            if(!$referencia->descuento){
                $referencia->precio = $valido['precio'];
            }
            if(isset($valido['maximo'])){
                $referencia->maximo_venta = $valido['maximo'];
            }
            $referencia->save();
            return redirect('productos')->withErrors(['success' => "Producto modificado"]);
        }catch(\Exception $e){
            return back()->withErrors([
                'danger' => 'Se ha producido un error en el sistema.'.(config('app.env')!="production" ? " ".$e->getMessage():"")
            ]);
        }
    }

    public function listar(){

        $referencias = Referencia::with(['producto','producto.fotos'=>function ($query) {
            $query->where('principal', 1);
        },'producto.categoria', 'producto.categoria.categoria','producto.categoria.categoria.categoria','producto.marca'])->where('tienda_id',Auth::id())->where('borrado',0)->get();
        $marcas = DB::table('marcas')->join('productos','marcas.id','=','productos.marca_id')->join('referencias','productos.id','=','referencias.producto_id')->where('referencias.tienda_id',Auth::id())->select('marcas.nombre')->orderBy('marcas.nombre','asc')->groupBy('marcas.nombre')->get();

        //Categorías asociadas a los productos para luego filtrar
        $categorias = [];

        foreach($referencias as $referencia){
            //Ver de qué nivel es la categoría del producto
            if($referencia->producto->categoria->categoria_id){//Subcategoría o subsubcategoría
                if($referencia->producto->categoria->categoria->categoria_id){//Subsubcategoría
                    
                    if(!isset($categorias[$referencia->producto->categoria->categoria->categoria->id])){//Categoría base no registrada
                        $categorias[$referencia->producto->categoria->categoria->categoria->id] = [
                            "nombre" => $referencia->producto->categoria->categoria->categoria->nombre,
                            "subcategorias" => [
                                $referencia->producto->categoria->categoria->id => [
                                    "nombre" => $referencia->producto->categoria->categoria->nombre,
                                    "subsubcategorias" => [
                                        $referencia->producto->categoria->id => $referencia->producto->categoria->nombre
                                    ]
                                ]
                            ]
                        ];
                    }
                    else if(!isset($categorias[$referencia->producto->categoria->categoria->categoria->id]["subcategorias"][$referencia->producto->categoria->categoria->id])){//Subcategoría no registrada
                        $categorias[$referencia->producto->categoria->categoria->categoria->id]["subcategorias"][$referencia->producto->categoria->categoria->id] = [
                                "nombre" => $referencia->producto->categoria->categoria->nombre,
                                "subsubcategorias" => [
                                    $referencia->producto->categoria->id => $referencia->producto->categoria->nombre
                                ]
                            ];
                    }
                    else if(!isset($categorias[$referencia->producto->categoria->categoria->categoria->id]["subcategorias"][$referencia->producto->categoria->categoria->id]["subsubcategorias"][$referencia->producto->categoria->id])){//Solo subsubcategoría no registrada
                        $categorias[$referencia->producto->categoria->categoria->categoria->id]["subcategorias"][$referencia->producto->categoria->categoria->id]["subsubcategorias"][$referencia->producto->categoria->id] = $referencia->producto->categoria->nombre;
                    }
                }
                else if(!isset($categorias[$referencia->producto->categoria->categoria->id])){//Categoría base no registrada
                    $categorias[$referencia->producto->categoria->categoria->id] = [
                        "nombre" => $referencia->producto->categoria->categoria->nombre,
                        "subcategorias" => [
                            $referencia->producto->categoria->id => [
                                "nombre" => $referencia->producto->categoria->nombre,
                                "subsubcategorias" => []
                            ]
                        ]
                    ];
                }else if(!isset($categorias[$referencia->producto->categoria->categoria->id]["subcategorias"][$referencia->producto->categoria->id])){//Solo subcategoría no registrada
                    $categorias[$referencia->producto->categoria->categoria->id]["subcategorias"][$referencia->producto->categoria->id] = [
                        "nombre" => $referencia->producto->categoria->nombre,
                        "subsubcategorias" => []
                    ];
                }
            }
            else if(!isset($categorias[$referencia->producto->categoria->id])){//Categoría base no registrada
                $categorias[$referencia->producto->categoria->id] = [
                    "nombre" => $referencia->producto->categoria->nombre,
                    "subcategorias" => []
                ];
            }
        }
        
        return view('productos.lista',["referencias"=>$referencias,"marcas" => $marcas,"categorias" => $categorias]);
    }

    public function borrar($id){
        try{
            $referencia = Referencia::find($id);
            if($referencia){
                if($referencia->pedidos){
                    $referencia->borrado = true;
                    $referencia->save();
                }else{
                    $referencia->delete();
                }
                return redirect(route('productos'))->withErrors(["success"=>"Producto eliminado"]);
            }else{
                return back()->withErrors(["warning"=>"No encontrado"]);
            }
        }catch(\Exception $e){
            return back()->withErrors([
                'danger' => 'Se ha producido un error en el sistema.'.(config('app.env')!="production" ? " ".$e->getMessage():"")
            ]);
        }
    }

    //Funciones AJAX
    public function aplicarDescuento(Request $request){
        $respuesta = ["resultado"=>1,"mensaje"=>"ok"];
        

        $validator = Validator::make($request->all(), [
            "id" => ["required","exists:referencias,id"],
            "descuento" => ["required","numeric","min:0.01"],
            "fecha" => ["nullable","date"],
        ],[
            'id.required' => 'No se ha especificado el producto',
            'id.exists' => 'No se ha encontrado el producto',
            'descuento.required' => 'No se ha indicado el descuento',
            'descuento.numeric' => 'El importe no es un número válido',
            'descuento.min' => 'El descuento es demasiado bajo',
            'fecha.date' => 'La fecha no tiene un formato válido',
        ]);
 
        if ($validator->fails()) {
            $respuesta["resultado"] = 0;
            $respuesta["mensaje"] = "Debes corregir los siguientes errores:";
            foreach($validator->errors()->all() as $error){
                $respuesta["mensaje"] .= "<br>$error";
            }
        }
        else{
            try{
                $valido = $validator->validated();
                $referencia = Referencia::find($valido['id']);
                //Validar además que fecha sea mayor a hoy y descuento menor a precio base del producto
                if(isset($valido["fecha"])){
                    $hoy = new \DateTime();
                    $fechaFin = new \DateTime($valido["fecha"]);
                    if($fechaFin<$hoy){
                        $respuesta["mensaje"] = "La fecha tiene que ser posterior a la del día de hoy";
                    }
                    $referencia->fin_descuento = $valido["fecha"];
                }
                
                if($valido["descuento"] >= $referencia->precio){
                    $respuesta["mensaje"] = "El descuento supera al precio del producto";
                }
                
                $referencia->descuento = $valido["descuento"];
                
                if($respuesta["mensaje"] == "ok"){
                    $referencia->save();
                }else{
                    $respuesta["resultado"] = 0;
                }
            }catch(\Exception $e){
                $respuesta["mensaje"] = 'Se ha producido un error en el sistema.'.(config('app.env')!="production" ? " ".$e->getMessage():"");
                $respuesta["resultado"] = 0;
            }

        }



        return response()->json($respuesta);
    }

    public function quitarDescuento(Request $request){
        $respuesta = ["resultado"=>1,"mensaje"=>"ok"];
        

        $validator = Validator::make($request->all(), [
            "id" => ["required","exists:referencias,id"]
        ],[
            'id.required' => 'No se ha especificado el producto',
            'id.exists' => 'No se ha encontrado el producto'
        ]);
 
        if ($validator->fails()) {
            $respuesta["resultado"] = 0;
            $respuesta["mensaje"] = "Debes corregir los siguientes errores:";
            foreach($validator->errors()->all() as $error){
                $respuesta["mensaje"] .= "<br>$error";
            }
        }
        else{
            try{
                $valido = $validator->validated();
                $referencia = Referencia::find($valido['id']);
                $referencia->fin_descuento = null;
                $referencia->descuento = null;
                $referencia->save();
            }catch(\Exception $e){
                $respuesta["mensaje"] = 'Se ha producido un error en el sistema.'.(config('app.env')!="production" ? " ".$e->getMessage():"");
                $respuesta["resultado"] = 0;
            }

        }
        return response()->json($respuesta);
    }

    public function aplicarDescuentoMasivo(Request $request){
        $respuesta = ["resultado"=>1,"mensaje"=>"ok"];
    
        $validator = Validator::make($request->all(), [
            "ids.*" => ["required","exists:referencias,id"],
            "descuento" => ["required","numeric","min:1"],
            "fecha" => ["nullable","date"],
        ],[
            'ids.required' => 'No se ha especificado el producto',
            'ids.exists' => 'No se ha encontrado el producto',
            'descuento.required' => 'No se ha indicado el descuento',
            'descuento.numeric' => 'El importe no es un número válido',
            'descuento.min' => 'El descuento es demasiado bajo',
            'fecha.date' => 'La fecha no tiene un formato válido',
        ]);
 
        if ($validator->fails()) {
            $respuesta["resultado"] = 0;
            $respuesta["mensaje"] = "Debes corregir los siguientes errores:";
            foreach($validator->errors()->all() as $error){
                $respuesta["mensaje"] .= "<br>$error";
            }
        }
        else{
            try{
                $valido = $validator->validated();
                //Validar además que fecha sea mayor a hoy y descuento menor a precio base del producto
                if(isset($valido["fecha"])){
                    $hoy = new \DateTime();
                    $fechaFin = new \DateTime($valido["fecha"]);
                    if($fechaFin<$hoy){
                        $respuesta["mensaje"] = "La fecha tiene que ser posterior a la del día de hoy";
                    }
                }
                
                if($respuesta["mensaje"] == "ok"){
                    $update = ['descuento' => DB::raw('`precio`*'.$valido['descuento']/100)];
                    if(isset($valido["fecha"])){
                        $update['fin_descuento']=$valido["fecha"];
                    }
                    $update["updated_at"] = new \DateTime();
                    DB::table('referencias')
                      ->whereIn('id', $valido['ids'])
                      ->update($update);
                }else{
                    $respuesta["resultado"] = 0;
                }
            }catch(\Exception $e){
                $respuesta["mensaje"] = 'Se ha producido un error en el sistema.'.(config('app.env')!="production" ? " ".$e->getMessage():"");
                $respuesta["resultado"] = 0;
            }

        }
        return response()->json($respuesta);
    }

    public function cambiarEstado(Request $request){
        $respuesta = ["resultado"=>1,"mensaje"=>"ok"];
        

        $validator = Validator::make($request->all(), [
            "id" => ["required","exists:referencias,id"]
        ],[
            'id.required' => 'No se ha especificado el producto',
            'id.exists' => 'No se ha encontrado el producto'
        ]);
 
        if ($validator->fails()) {
            $respuesta["resultado"] = 0;
            $respuesta["mensaje"] = "Debes corregir los siguientes errores:";
            foreach($validator->errors()->all() as $error){
                $respuesta["mensaje"] .= "<br>$error";
            }
        }
        else{
            try{
                $valido = $validator->validated();
                $referencia = Referencia::find($valido['id']);
                if($referencia->disponible){
                    $referencia->disponible = 0;
                }else{
                    $referencia->disponible = 1;
                }
                $referencia->save();
                $respuesta["mensaje"] = $referencia->disponible;
            }catch(\Exception $e){
                $respuesta["mensaje"] = 'Se ha producido un error en el sistema.'.(config('app.env')!="production" ? " ".$e->getMessage():"");
                $respuesta["resultado"] = 0;
            }

        }
        return response()->json($respuesta);
    }
}
