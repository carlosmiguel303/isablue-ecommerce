import { Component } from '@angular/core';

@Component({
  selector: 'app-whatsapp',
  templateUrl: './whatsapp.component.html',
  styleUrls: ['./whatsapp.component.css']
})
export class WhatsappComponent {
  // Número de WhatsApp de Isablue (Perú +51)
  readonly link = 'https://wa.me/51920097746?text=' +
    encodeURIComponent('¡Hola Isablue! Quiero información sobre sus juguetes 🧸');
}
