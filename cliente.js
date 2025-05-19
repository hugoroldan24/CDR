function handleLoginClick() {
     const creds = getCredentials();
     const url = buildLoginUrl(creds);
     performRequest(url,updateLoggedUser);
}

function handleQueryClick() {
     const query = document.getElementById('user-query').value;
     const url = buildQueryUrl(query);
     performRequest(url,updateSentQuery);
}

function handleLogoutClick(){
     const url = "http://localhost:8000/Servidor/logout.php";
     performRequest(url,updateUnloggedUser);
}

function updateLoggedUser(response){
     const error_login = document.getElementById('login-message');
     if (response.status === 'id_not_matched'){ //Si no coincide con nada en la base de datos
        
        error_login.textContent = 'Usuario o contraseña incorrectos';
        error_login.classList.remove('hidden');   
        error_login.classList.add('error'); //Para el css            
     }
     else{ //'id_matched'
        error_login.classList.add('hidden'); //Para el css
        document.getElementById('login-section').classList.add('hidden');                //Escondemos la página de log in
        const query_page = document.getElementById('query-section');                     //Obtenemos una referencia a la pagina de querys
        query_page.classList.remove('hidden');                                           //Hacemos visible la página de querys
        const label = document.getElementById('welcome-label');
        label.textContent= `Welcome ${response.data.name}!`;                              //Ponemos el texto en el label del HTML  
     }
}
function updateSentQuery(response){
   if(response.status === 'expired'){
     updateUnloggedUser(response);  //Si nos llega ese status, querrá decir que se ha cumplido el tiempo de inactividad
     return;
   }
   const error_query = document.getElementById('query-message');
   const container = document.getElementById('query-results');   //Contenedor donde irá la tabla
   error_query.classList.add('hidden');
   if (response.status === 'valid_query'){
     const data = response.data;
        
     const error_query = document.getElementById('query-message'); //elimina el misatge de query inválida
     error_query.classList.add('hidden');
     
     container.innerHTML = ''; //Esto borrara la tabla anterior
     
     const table = document.createElement('table');                //Añadimos aquí una tabla
     table.classList.add('results-table');

     const thead = document.createElement('thead');   //thead es una etiqueta HTML para agrupar el encabezado de una tabla, osea para meter dentro los nombres de las columnas
     const headerRow = document.createElement('tr');  //Dentro de la etiqueta de 'tr' se ponen los nombres de las columnas, (date, mark, room ... )

     const excluded_keys = ['id','uid','day_int'];
     Object.keys(data[0]).forEach(key => {               //Object.keys() te devuelve un array con todas las claves (nombres de las columnas). Luego con el .forEach recorremos todas las keys y las introducimos en las etiquetas th las cuales luego se introducen en la etiqueta tr
        if(excluded_keys.includes(key)){
             return;
        }
        const column_name = document.createElement('th');
        column_name.textContent = key; //Ponemos que en la celda tenga el nombre de la columa
        headerRow.appendChild(column_name); //Introducimos la celda th en la etiqueta tr
     });
     thead.appendChild(headerRow); //Añadimos la fila de los nombres de las columnas en el thead
     table.appendChild(thead);     //Añadimos ahora el encabezado de la tabla en la etiqueta de table

     const table_body = document.createElement('tbody'); //Creamos la etiqueta que contendrá las filas de la tabla , osea la información
     data.forEach(fila_vector => { //Hacemos un for-each por las filas de info
       const fila = document.createElement('tr'); //Este es el contenedor donde irá una fila
       Object.entries(fila_vector).forEach(([key,value]) => { //Hacemos un for each de cada elemento particular de una fila concreta. Object.entries te devuelve la pareja key (titulo columna. ex : subject) value(valor. ex : PBE
          if(excluded_keys.includes(key)){
             return;
          }
          const celda = document.createElement('td'); //Creamos una celda para poner un dato
          celda.textContent = value;
          fila.appendChild(celda); //Introducimos la celda del dato en el contenedor de la fila
       });
       table_body.appendChild(fila); //Introducimos la fila en la tabla que contendrá TODAS las filas
     });
     table.appendChild(table_body);  //Introducimos a la tabla todas las filas
     container.appendChild(table)  //Finalmente, añadimos la tabla en el contenedor
   }  
   else{
     container.innerHTML = ''; //Esto borrara la tabla anterior
     error_query.textContent = 'Query invalida';
     error_query.classList.remove('hidden');   
     error_query.classList.add('error');      
   }
}
function updateUnloggedUser(response){
     const container = document.getElementById('query-results');   
     container.innerHTML = ''; //Esborrem la taula anterior
     ['user-id', 'user-pass','user-query'].forEach(id => {
          document.getElementById(id).value = "";  //Borramos el contenido que pusimos en los formularios
     });
     document.getElementById('query-message').classList.add('hidden'); //Escondemos el mensaje de query errónea en caso de que estuviera
     document.getElementById('login-section').classList.remove('hidden'); //Mostramos de nuevo la pagina de log in
     document.getElementById('query-section').classList.add('hidden'); //Escondemos la pagina de querys
     console.log(response);
}
     
document
  .getElementById('login-button')
  .addEventListener('click', handleLoginClick);        //Estas lineas lo que hacen es como el connect de python, hacer que se ejecuten esas funciones cuando se clicke en sus respectivos botones
document
  .getElementById('query-button')
  .addEventListener('click',handleQueryClick);
document
  .getElementById('logout-button')
  .addEventListener('click',handleLogoutClick);


  


  



