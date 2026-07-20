import { Component, OnDestroy, OnInit } from '@angular/core';
import { Promo, PromoService } from 'src/app/services/promo.service';
import { SessionStorageService } from 'src/app/services/session-storage.service';

@Component({
  selector: 'app-promo-carousel',
  templateUrl: './promo-carousel.component.html',
  styleUrls: ['./promo-carousel.component.css']
})
export class PromoCarouselComponent implements OnInit, OnDestroy {
  promos: Promo[] = [];
  current = 0;
  private timer: any;

  constructor(private promoService: PromoService, private session: SessionStorageService) {}

  ngOnInit(): void {
    this.promoService.getAll().subscribe({
      next: p => { this.promos = p || []; this.current = 0; this.start(); },
      error: () => { this.promos = []; }
    });
  }

  ngOnDestroy(): void { this.stop(); }

  start(): void {
    this.stop();
    if (this.promos.length > 1) {
      this.timer = setInterval(() => this.next(), 5500);
    }
  }
  stop(): void { if (this.timer) { clearInterval(this.timer); this.timer = null; } }

  next(): void { this.current = (this.current + 1) % this.promos.length; }
  prev(): void { this.current = (this.current - 1 + this.promos.length) % this.promos.length; }
  go(i: number): void { this.current = i; this.start(); }

  isAdmin(): boolean { return this.session.getItem('token')?.type === 'ADMIN'; }
}
