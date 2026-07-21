import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { InfoPage, InfoService } from 'src/app/services/info.service';

@Component({
  selector: 'app-info-page',
  templateUrl: './info-page.component.html',
  styleUrls: ['./info-page.component.css']
})
export class InfoPageComponent implements OnInit {
  page: InfoPage | null = null;
  loading = true;

  constructor(private route: ActivatedRoute, private infoService: InfoService) {}

  ngOnInit(): void {
    this.route.paramMap.subscribe(params => {
      const key = params.get('key') || '';
      this.loading = true;
      this.infoService.get(key).subscribe({
        next: p => { this.page = p; this.loading = false; },
        error: () => { this.loading = false; }
      });
    });
  }
}
