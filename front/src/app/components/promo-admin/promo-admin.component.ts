import { Component, OnInit } from '@angular/core';
import { ToastrService } from 'ngx-toastr';
import { Promo, PromoService } from 'src/app/services/promo.service';

@Component({
  selector: 'app-promo-admin',
  templateUrl: './promo-admin.component.html',
  styleUrls: ['./promo-admin.component.css']
})
export class PromoAdminComponent implements OnInit {
  promos: Promo[] = [];
  edit: Promo = this.blank();

  private readonly palettes = [
    { bg: 'linear-gradient(120deg,#c87a5b 0%,#d99a7c 100%)', text: '#fff6ef' },
    { bg: 'linear-gradient(120deg,#4a3a30 0%,#7a5b45 100%)', text: '#f7ede2' },
    { bg: 'linear-gradient(120deg,#e7b96b 0%,#f0cf8f 100%)', text: '#4a3a30' },
    { bg: 'linear-gradient(120deg,#8a9a7b 0%,#aec29a 100%)', text: '#2f3a26' }
  ];

  constructor(private promoService: PromoService, private toastr: ToastrService) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.promoService.getAll().subscribe({
      next: p => this.promos = p || [],
      error: () => this.toastr.error('No se pudieron cargar las promociones.')
    });
  }

  blank(): Promo {
    return { id: 0, title: '', subtitle: '', cta: 'Ver más', link: '#productos', image: '',
      bg: 'linear-gradient(120deg,#c87a5b 0%,#d99a7c 100%)', text: '#fff6ef' };
  }

  selectPalette(p: {bg: string; text: string}): void { this.edit.bg = p.bg; this.edit.text = p.text; }
  palettesList() { return this.palettes; }

  startNew(): void { this.edit = this.blank(); }
  startEdit(p: Promo): void { this.edit = { ...p }; }

  onFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (!input.files || !input.files[0]) { return; }
    const reader = new FileReader();
    reader.onload = () => { this.edit.image = reader.result as string; };
    reader.readAsDataURL(input.files[0]);
  }

  save(): void {
    if (!this.edit.title.trim()) { this.toastr.error('Escribe un título para la promoción.'); return; }
    if (this.edit.id) {
      this.promoService.update(this.edit).subscribe({
        next: () => { this.toastr.success('Promoción actualizada.'); this.afterSave(); },
        error: () => this.toastr.error('No se pudo guardar la promoción.')
      });
    } else {
      const { id, ...rest } = this.edit;
      this.promoService.add(rest).subscribe({
        next: () => { this.toastr.success('Promoción agregada.'); this.afterSave(); },
        error: () => this.toastr.error('No se pudo agregar la promoción.')
      });
    }
  }

  private afterSave(): void { this.load(); this.edit = this.blank(); }

  remove(p: Promo): void {
    this.promoService.remove(p.id).subscribe({
      next: () => { this.toastr.info('Promoción eliminada.'); this.load(); if (this.edit.id === p.id) { this.edit = this.blank(); } },
      error: () => this.toastr.error('No se pudo eliminar la promoción.')
    });
  }

  restore(): void {
    this.promoService.reset().subscribe({
      next: p => { this.promos = p || []; this.edit = this.blank(); this.toastr.success('Promociones restauradas.'); },
      error: () => this.toastr.error('No se pudieron restaurar las promociones.')
    });
  }
}
