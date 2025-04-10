
import gi
gi.require_version("Gtk", "3.0")
from gi.repository import Gtk, GLib

import threading
import requests

from lector import obtener_uid
from lcd_display import show_message

SERVER_URL = "http://IP_DEL_SERVIDOR:PORT/auth"  # ← Canvia-ho

class AteneaClient(Gtk.Window):
    def __init__(self):
        Gtk.Window.__init__(self, title="Atenea - Escaneja la targeta")
        self.set_border_width(10)
        self.set_default_size(300, 200)

        # Layout principal
        self.box = Gtk.Box(orientation=Gtk.Orientation.VERTICAL, spacing=6)
        self.add(self.box)

        self.label = Gtk.Label(label="Escaneja la teva targeta...")
        self.box.pack_start(self.label, True, True, 0)

        self.name_label = Gtk.Label()
        self.box.pack_start(self.name_label, True, True, 0)

        self.retry_button = Gtk.Button(label="Tornar a escanejar")
        self.retry_button.connect("clicked", self.restart_scan)
        self.retry_button.set_sensitive(False)
        self.box.pack_start(self.retry_button, True, True, 0)

        show_message("Escaneja la", "teva targeta...")
        self.start_uid_thread()

    def start_uid_thread(self):
        thread = threading.Thread(target=self.read_uid_loop, daemon=True)
        thread.start()

    def read_uid_loop(self):
        try:
            uid = obtener_uid()  # ← Aquí fas servir la teva llibreria
            GLib.idle_add(self.process_uid, uid)
        except Exception as e:
            GLib.idle_add(self.show_error, f"Error llegint: {e}")

    def process_uid(self, uid):
        self.label.set_text("Validant UID...")
        show_message("Validant UID...")

        try:
            response = requests.post(SERVER_URL, json={"uid": uid})
            if response.status_code == 200:
                name = response.json().get("name", "Desconegut")
                self.name_label.set_text(f"Benvingut/da, {name}!")
                show_message("Benvingut/da:", name)
            else:
                self.show_error("UID no vàlid")
        except:
            self.show_error("Error de connexió")

        self.retry_button.set_sensitive(True)

    def show_error(self, message):
        self.name_label.set_text(message)
        show_message("Error:", message[:16])

    def restart_scan(self, widget):
        self.name_label.set_text("")
        self.label.set_text("Escaneja la teva targeta...")
        show_message("Escaneja la", "teva targeta...")
        self.retry_button.set_sensitive(False)
        self.start_uid_thread()

win = AteneaClient()
win.connect("destroy", Gtk.main_quit)
win.show_all()
Gtk.main()
