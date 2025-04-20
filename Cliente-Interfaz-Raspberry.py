import gi
gi.require_version("Gtk", "3.0")
from gi.repository import Gtk, GLib
import requests #añadido para los requests al servidor
import threading
import requests
import time
import lcddriver
import json

from lector import obtener_uid


SERVER_URL = "http://IP_DEL_SERVIDOR:PORT/auth"  # <-- CAMBIAR ESTO CUANDO SEPA LA IP DEL SERVIDOR!

class AteneaClient(Gtk.Window):
    def __init__(self):
        super().__init__(title="Client Atenea")
        self.server = #añadir IP server
        self.port = #añadir puerto
  
        
        #configuracion ventana
        self.set_border_width(10)
        self.set_default_size(400, 300)
        self.connect("destroy", self.on_destroy) # conecta la señal destroy del objeto(la ventana) a un metodo handler (self.on_destroy)

        # Layout principal
        self.stack = Gtk.Stack()  
        self.stack.set_transition_type(Gtk.StackTransitionType.SLIDE_LEFT_RIGHT)
        self.stack.set_transition_duration(300)
        self.add(self.stack)
        
        self.loginbox = Gtk.Box(orientation=Gtk.Orientation.VERTICAL, spacing=6)
        self.stack.add_named(self.loginbox, "login")
        self.loginbox.set_margin_top(20)
        self.loginbox.set_margin_bottom(20)
        self.loginbox.set_margin_start(20)
        self.loginbox.set_margin_end(20)

        # Info label
        self.loginlabel = Gtk.Label(label="PLEASE, LOGIN WITH YOUR UNIVERSITY CARD")
        self.loginbox.pack_start(self.loginlabel, False, False, 0)
        


        # TextView per mostrar info del servidor
        self.textview = Gtk.TextView()
        self.textview.set_editable(False)
        vbox.pack_start(self.textview, True, True, 0)

        #Pantalla query
        self.querybox = Gtk.Box(orientation=Gtk.Orientation.VERTICAL, spacing=6)
        self.stack.add_named(self.querybox, "query")
        self.querybox.set_margin_top(10)
        self.querybox.set_margin_bottom(10)
        self.querybox.set_margin_start(10)
        self.querybox.set_margin_end(10)
        
        self.welcomelabel = Gtk.Label(label="WELCOME")
        self.querybox.pack_start(self.welcomelabel, False, False, 0)

        self.query_entry = Gtk.Entry()                                   #nos permite escribir aquello que ordenamos  
        self.query_entry.set_placeholder_text("Type your query:")         
        self.query_entry.connect("activate", self.on_query)               #volvemos a conectar la señal activate al metodo in_query.
        self.querybox.pack_start(self.query_entry, False, False, 0)
        
        
        self.logout_button = Gtk.Button(label="Logout")                   #ponemos un boton para deslogear
        self.logout_button.connect("clicked", self.on_logout)               #conectamos la señal clicked al metodo logout
        self.querybox.pack_start(self.logout_button, False, False, 0)

        # Comença la lectura del UID
        self.start_uid_thread()

        #se inicializa de manera que lo primero que salga sea la login screen
        self.stack.set_visible_child_name("login")

        #importante hace que todo se vea visible
        self.show_all()


    def start_uid_thread(self):
        thread = threading.Thread(target=self.read_uid_loop, daemon=True)
        thread.start()

    def read_uid_loop(self):
        try:
            uid = obtener_uid()
            GLib.idle_add(self.process_uid, uid)
       

    def process_uid(self, uid):
        self.label.set_text("Validant targeta en la base de dades...")
        self.display_message("Validant targeta en la base de dades...")

        try:
            response = requests.post(SERVER_URL, json={"uid": uid})
            if response.status_code == 200:
                # Si el UID es válido
                name = response.json().get("name", "status")
                self.display_message(f"Benvingut/da, {name}!")
                self.update_welcome_screen(name)
                # Llamamos a la función para mostrar el mensaje en la LCD
                mostrar_lcd(name)
            else:
                # Si el UID no está en la base de datos
                self.update_loginlabel("User not found", "red")
                    except requests.exceptions.RequestException as e:
                self.update_loginlabel(f"Connection error: {str(e)}", "red")
                    except ValueError:
                self.update_login_label("Invalid server response", "red")
                
      def mostrar_lcd(name):
          lcd = lcddriver.lcd()
          lcd.lcd_clear() 
          message = f"Benvingut {name}"
          lines = message.split("\n")
          for i, line in enumerate(lines[:4]):
          lcd.lcd_display_string(line[:20], i + 1)



      def on_destroy(self, widget):                                   #metodo para cerrar aplicacion.
        print("Ventana cerrada. Saliendo de la aplicación.")
        Gtk.main_quit()

      


      def on_logout(self, button):                                         #funcion log out, resetea timer, muestra el widjet hijo ventana log in
        GLib.source_remove(self.timer)
        self.timer = None
        self.stack.set_visible_child_name("login")
        self.loginlabel.set_text("Please login with your university card")
          
      def on_query(self, widget):
        queryname = self.query_entry.get_text().strip()                                      #referencia query_entry de la parte de la pantalla query
        if queryname:
            url = f"http://{self.server}:{self.port}/server.php/{self.uid}/{queryname}"        #creamos un url para enviar una request al server
            threading.Thread(target=self.do_query, args=(url,)).start()                   #hilo para que no se pare la ui , importante
        else:
            self.update_welcomelabel("No query found!", "red")     #hacer funcion

      def do_query(self,url):
            try:
            response = requests.get(url, timeout=5)
            response.raise_for_status()  #lanza excepcion si estado no es 200
            data= response.json();
          
            def handle_response():                                                              #(self,data) si estubisese fuera de la otra funcion
            if not data or not isinstance(data, list):                                          #isinstance pregunta si el objeto(data) es de tipo (list) que es nuestro unico formato en las bases de datos por lo que necesitamos que sea tipo list
                self.update_welcomelabel("No data found or invalid format!", "red")
                return                                                                    #para que GLib.idle_add no lo repita
            self.create_table(data)
          

        GLib.idle_add(self.handle_response,data)

    except requests.exceptions.RequestException as e:
        GLib.idle_add(self.update_welcomelabel, f"Connection error: {str(e)}", "red")
           

        def create_table (self, json_array):
            #acabar
            if not json_array
                print("No data")
            
            if hasattr(self, "treeview"):           #si el objeto ya tenia una tabla anterior la elimina.
                self.treeview.destroy()

            keys = list(json_array[0].keys())
            if not keys:
                print("No keys found in data")
                return

            self.list = Gtk.ListStore(*[str] * len(keys))  #sirve para almacenar filas y columnas en un tree view, el * sirve para desempaquetar el numero de keys en strings (numero columnas).

            for item in json_array                                               #recorremos cada diccionario 
                fila= [str(item.get(key, "-")) for key in keys]                   #iteramos en su numero de claves
                self.list.append(fila)                                            #añadimos  las filas

              self.treeview = Gtk.TreeView(model=self.list)

            for i, key in enumerate(keys):
                renderer = Gtk.CellRendererText()
                columna = Gtk.TreeViewColumn(key, renderer, text=i)
                self.treeview.append_column(columna)

             self.treeview.modify_font(Pango.FontDescription("Helvetica italic 12"))
             self.treeview.get_header().modify_font(Pango.FontDescription("Helvetica bold 14"))

             self.query_box.pack_start(self.treeview, True, True, 0)
             self.query_box.show_all()



        def update_loginlabel(self, text, color="red"):
            self.loginlabel.set_markup(f'<span foreground="{color}">{text}</span>')

        def update_welcome_screen(self, name):
            self.welcomelabel.set_text(f"Benvingut/da, {name}!")
            self.stack.set_visible_child_name("query")
           


if __name__ == "__main__":
    win = AteneaClient()
    #win.connect("destroy", Gtk.main_quit)
    win.show_all()
    Gtk.main()

