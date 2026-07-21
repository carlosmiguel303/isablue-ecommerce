import { Component, OnInit } from '@angular/core';
import { ToastrService } from 'ngx-toastr';
import { InfoPage, InfoService } from 'src/app/services/info.service';

@Component({
  selector: 'app-info-admin',
  templateUrl: './info-admin.component.html',
  styleUrls: ['./info-admin.component.css']
})
export class InfoAdminComponent implements OnInit {
  pages: InfoPage[] = [];
  edit: InfoPage | null = null;
  saving = false;

  constructor(private infoService: InfoService, private toastr: ToastrService) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.infoService.list().subscribe({
      next: p => { this.pages = p || []; if (!this.edit && this.pages.length) { this.select(this.pages[0]); } },
      error: () => this.toastr.error('No se pudieron cargar las páginas.')
    });
  }

  select(p: InfoPage): void { this.edit = { ...p }; }

  save(): void {
    if (!this.edit || this.saving) { return; }
    if (!this.edit.title.trim()) { this.toastr.error('Escribe un título.'); return; }
    this.saving = true;
    this.infoService.update(this.edit.pageKey, { title: this.edit.title, content: this.edit.content }).subscribe({
      next: () => { this.saving = false; this.toastr.success('Página actualizada. Ya la ven tus clientes.'); this.load(); },
      error: () => { this.saving = false; this.toastr.error('No se pudo guardar.'); }
    });
  }
}
