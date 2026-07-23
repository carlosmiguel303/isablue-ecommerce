import { Component } from '@angular/core';
import { environment } from 'src/environments/environment';

@Component({
  selector: 'app-whatsapp',
  templateUrl: './whatsapp.component.html',
  styleUrls: ['./whatsapp.component.css']
})
export class WhatsappComponent {
  private readonly phone = environment.store?.whatsapp || '';
  readonly enabled = !!this.phone;
  readonly link = this.phone
    ? 'https://wa.me/' + this.phone + '?text=' +
      encodeURIComponent('¡Hola! Quiero más información.')
    : '';
}
