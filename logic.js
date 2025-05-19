class ServerCommunication {
  constructor(url,callback){
     this.url = url;
     this.callback = callback;
     this.xhr = null;    
  }
  sendRequest(){
     this.xhr = new XMLHttpRequest();
     this.xhr.open('GET', this.url, true);
     this.xhr.onload =  () => {
      this.handleServerResponse();
     };
     this.xhr.onerror = () => {
      console.error('Error de conexión');
     };
     this.xhr.send();    
  }
  handleServerResponse(){
    const xhr = this.xhr;
    if(xhr.status === 200){
      try{
        const response = JSON.parse(xhr.responseText);       //Parseamos la respuesta .json del server
        this.callback(response);     
      }catch(e){
        console.error('Error al procesar JSON:', e);
      }
    }
    else{
       console.error(`Error del servidor: ${xhr.status}`);
     }
   }
}

function buildLoginUrl({ id, pass }) {
  const base   = 'http://localhost:8000/Servidor/login.php/students?';
  const params = `username=${encodeURIComponent(id)}&pass=${encodeURIComponent(pass)}`;
  return base + params;
}

function buildQueryUrl(query) {
  const base = `http://localhost:8000/Servidor/querys.php/${query}?`;
  let constraints;
  switch (query) {
    case "marks": constraints = ""; break;
    case "timetables": constraints = "day=now&hour[lt]=now"; break;
    case "tasks": constraints = "date[gte]=now"; break;
    default: constraints = null; // Invalid table
   }
return base + constraints;
}

function performRequest(url,callback){
   const com = new ServerCommunication(url,callback);
   com.sendRequest();
}

// Obtiene las credenciales desde el formulario
function getCredentials() {
  const id   = document.getElementById('user-id').value;
  const pass = document.getElementById('user-pass').value;
  return { id, pass };
}
