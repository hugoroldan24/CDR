import gi
gi.require_version("Gtk", "3.0")
from gi.repository import Gtk, GLib

import threading
import requests

from lector import obtener_uid
from lcd_display import show_multiline_text

SERVER_URL = "http://IP_DEL_SERVIDOR:PORT/auth"  # <-- CAMBIAR ESTO CUANDO SEPA LA IP DEL SERVIDOR!

class AteneaClient(Gtk.Window):
    def __init__(self):
        super().__init__(title="Client Atenea")
        self.set_border_width(10)
        self.set_default_size(400, 300)

        # Layout principal
        vbox = Gtk.Box(orientation=Gtk.Orientation.VERTICAL, spacing=6)
        self.add(vbox)

        # Info label
        self.label = Gtk.Label(label="PLEASE, LOGIN WITH YOUR UNIVERSITY CARD")
        vbox.pack_start(self.label, False, False, 0)

        # TextView per mostrar info del servidor
        self.textview = Gtk.TextView()
        self.textview.set_editable(False)
        vbox.pack_start(self.textview, True, True, 0)

        # Comença la lectura del UID
        self.start_uid_thread()

    def start_uid_thread(self):
        thread = threading.Thread(target=self.read_uid_loop, daemon=True)
        thread.start()

    def read_uid_loop(self):
        try:
            uid = obtener_uid()
            GLib.idle_add(self.process_uid, uid)
        except Exception as e:
            GLib.idle_add(self.display_message, f"Error llegint UID: {e}")

    def process_uid(self, uid):
        self.label.set_text("Validant targeta en la base de dades...")
        self.display_message("Validant targeta en la base de dades...")

        try:
            response = requests.post(SERVER_URL, json={"uid": uid})
            if response.status_code == 200:
                # Si el UID es válido
                name = response.json().get("name", "Desconegut")
                self.label.set_text(f"Benvingut/da, {name}!")
                self.display_message(f"Benvingut/da, {name}!")
            else:
                # Si el UID no está en la base de datos
                self.label.set_text("UID incorrecte. Torna-ho a intentar.")
                self.display_message("UID incorrecte. Torna-ho a intentar.")
        except Exception as e:
            self.label.set_text("Error de connexió amb el servidor.")
            self.display_message(f"Error de connexió: {str(e)}")

    def display_message(self, text):
        show_multiline_text(text)

    def set_textview(self, text):
        buffer = self.textview.get_buffer()
        buffer.set_text(text)

if __name__ == "__main__":
    win = AteneaClient()
    win.connect("destroy", Gtk.main_quit)
    win.show_all()
    Gtk.main()

