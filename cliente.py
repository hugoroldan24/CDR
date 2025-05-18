import gi
gi.require_version("Gtk", "3.0")
from gi.repository import Gtk, GLib, Pango
import threading
import time
import lcddriver
import json
import requests
from librerias import puzzle_1

# Clase para encapsular el thread de lectura de UID

class UIDReaderThread(threading.Thread):
    def __init__(self, callback):
        super().__init__(daemon=True)
        self._running = threading.Event()
        self._running.set()
        self.callback = callback  # Función a ejecutar cuando se lee un UID

    def run(self):
        while self._running.is_set():
            uid = puzzle_1.obtener_uid()
            if uid:
                GLib.idle_add(self.callback, uid)
    def stop(self):
        self._running.clear()


class AteneaClient(Gtk.Window):
    
    def __init__(self):
        super().__init__(title="Client Atenea")
        self.editor_css = Gtk.CssProvider()
        self.uid_thread = UIDReaderThread(self.process_uid)
        self.session = requests.Session() //Creamos la sesión
       
        self.server = "localhost"  # Cambiar por la IP del servidor
        self.port = "8000"  # Cambiar por el puerto del servidor     
        
    def start_window(self):
         # Configuración de la ventana
        self.set_border_width(10)
        self.set_default_size(400, 300)
        self.connect("destroy", self.on_destroy)
       
        self.configure_stack()      
        self.login_screen()     
        self.query_screen()
        self.configure_style_CSS()
        
        self.uid_thread.start()
        self.stack.set_visible_child_name("login")         # Mostrar la pantalla de login al inicio
        self.show_all()
        
     # Layout principal usando Stack    
    def configure_stack(self):
        self.stack = Gtk.Stack()
        self.stack.set_transition_type(Gtk.StackTransitionType.SLIDE_LEFT_RIGHT)
        self.stack.set_transition_duration(300)
        self.add(self.stack)
        
    # Pantalla de login   
    def login_screen(self):
        self.loginbox = Gtk.Box(orientation=Gtk.Orientation.VERTICAL, spacing=6)
        self.loginbox.set_name("login_box")
        self.stack.add_named(self.loginbox, "login")
        
        self.loginlabel = Gtk.Label(label="PLEASE, LOGIN WITH YOUR UNIVERSITY CARD")
        self.loginbox.pack_start(self.loginlabel, False, False, 0)
        
     # Pantalla de consulta    
    def query_screen(self):
        self.querybox = Gtk.Box(orientation=Gtk.Orientation.VERTICAL, spacing=6)
        self.loginbox.set_name("query_box")
        self.stack.add_named(self.querybox, "query")
        

        self.welcomelabel = Gtk.Label(label="WELCOME")
        self.welcomelabel.set_name("welcome_label")
        self.querybox.pack_start(self.welcomelabel, False, False, 0)

        self.query_entry = Gtk.Entry()
        self.query_entry.set_placeholder_text("Type your query:")
        self.query_entry.connect("activate", self.on_query)
        self.querybox.pack_start(self.query_entry, False, False, 0)

        self.logout_button = Gtk.Button(label="Logout")
        self.logout_button.connect("clicked", self.on_logout)
        self.querybox.pack_start(self.logout_button, False, False, 0)
    
    # Procesar el UID recibido
    def process_uid(self, uid):
        self.update_loginlabel("Validando tarjeta en la base de datos...", "blue")
        url = f"http://{self.server}:{self.port}/Servidor/login.php?id={uid}"
        data = http_get(self.session,url)

        if data:
            name = data.get("data", "Desconocido")
            self.update_welcome_screen(name)
            self.mostrar_lcd(name)
        else:
            self.update_loginlabel("Usuario no encontrado", "red")

    # Mostrar nombre en el LCD
    def mostrar_lcd(self, name):
        lcd = lcddriver.lcd()
        lcd.lcd_clear()
        message = f"Benvingut {name}"
        lines = message.split("\n")
        for i, line in enumerate(lines[:4]):
            lcd.lcd_display_string(line[:20], i + 1)

    # Cuando se destruye la ventana
    def on_destroy(self, widget):
        print("Ventana cerrada. Saliendo de la aplicación.")
        self.uid_thread.stop()
        Gtk.main_quit()
    
    # Logout
    def on_logout(self, button):
        url = f"http://{self.server}:{self.port}/Servidor/logout.php?"
        http_get(self.session,url) //No usaremos la respuesta, por tanto lo podemos poner sin igualarle ninguna variable
        self.stack.set_visible_child_name("login")      
        self.query_entry.set_text("")
        self.loginlabel.set_text("Please login with your university card")
        self.treeview.destroy()
        self.treeview = None  # Eliminamos la referencia

    # Cuando se envía una consulta
    def on_query(self, widget):     
        table = self.query_entry.get_text().strip()
        match table:
            case 'marks': constraints = ""   
            case 'timetables': constraints = "day=now&hour[lt]=now"
            case 'tasks': constraints = "date[gte]=now" 
            case _: self.update_welcomelabel("Invalid table", "red")                
        if table:
             url = f"http://{self.server}:{self.port}/Servidor/querys.php/{table}?{constraints}"
             threading.Thread(target=self.do_query, args=(url,), daemon=True).start()
        else:
            self.update_welcomelabel("No query found!", "red")
        return    

    # Ejecutar la consulta
    def do_query(self, url):
        raw = http_get(self.session,url) 
        if (raw.get("status",[]) == 'expired'):
            self.on_logout()
            return
        
        if raw:
            GLib.idle_add(self.create_table, raw.get("data",[])) //Le pasamos por parametros una lista de diccionarios
        else:
            GLib.idle_add(self.update_welcomelabel, "Connection error", "red")

    # Crear tabla con resultados
    def create_table(self, json_array):
       if not json_array:
            print("No data")
            return
       if getattr(self, "treeview", None):
            self.treeview.destroy()
            self.treeview = None

        exclude_keys = {"uid", "day_int","id"}
        keys = [k for k in json_array[0].keys() if k not in exclude_keys]
        self.list = Gtk.ListStore(*[str] * len(keys))
        for item in json_array:
            fila = [str(item.get(key, "-")) for key in keys]
            self.list.append(fila)
        self.treeview = Gtk.TreeView(model=self.list)
       
        for i, key in enumerate(keys):
            renderer = Gtk.CellRendererText()
            columna = Gtk.TreeViewColumn(key, renderer, text=i)
            self.treeview.append_column(columna)
            
        self.treeview.set_name("treeview")
        self.querybox.pack_start(self.treeview, True, True, 0)
        self.querybox.show_all()
        
    # Actualizar etiqueta de login
    def update_loginlabel(self, text, color="red"):
        safe_text = str(text).replace('&', '&amp;').replace('<', '&lt;')
        self.loginlabel.set_markup(f'<span foreground="{color}">{safe_text}</span>')

    # Actualizar etiqueta de bienvenida
    def update_welcomelabel(self, text, color="red"):
        safe_text = str(text).replace('&', '&amp;').replace('<', '&lt;')
        self.welcomelabel.set_markup(f'<span foreground="{color}">{safe_text}</span>')

    # Actualizar pantalla de bienvenida tras login
    def update_welcome_screen(self, name):
        self.welcomelabel.set_text(f"Benvingut/da, {name}!")
        self.stack.set_visible_child_name("query")

    def configure_style_CSS(self):                                           
        self.editor_css.load_from_path("style.css")                                                              #Carreguem les regles d'estil CSS del fitxer estils.css
        screen = Gdk.Screen.get_default()                                                                         #Obtenim una referència a la pantalla de la aplicació
        Gtk.StyleContext.add_provider_for_screen(screen,self.editor_css,Gtk.STYLE_PROVIDER_PRIORITY_APPLICATION)  #Apliquem les regles CSS als widgets de la finestra.



def http_get(session,url):
    try:
        response = session.get(url,timeout=10)  //Si ya tenemos cookies guardadas , se añadiran automáticamente 
        if response.status_code == 200:
            session.cookies.get_dict()             //Guardamos las cookies en la Sesion
            print(f"URL final: {response.url}\n")
            data = response.json()
            if data:
                return data
            else:
                print("Empty response from server.")
                return None
        else:
            print(f"HTTP Error: {response.status}")
            return None            
    except Exception as e:
        print(f"Error HTTP: {e}")
        return None

if __name__ == "__main__":
    win = AteneaClient()
    win.start_window()
    win.show_all()
    Gtk.main()
