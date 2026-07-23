import { Component, OnInit } from '@angular/core';
import { HomeService, Categoria } from 'src/app/services/home.service';
import { Product } from 'src/app/common/product';
import { environment } from 'src/environments/environment';

@Component({
  selector: 'app-landing',
  templateUrl: './landing.component.html',
  styleUrls: ['./landing.component.css']
})
export class LandingComponent implements OnInit {
  categories: Categoria[] = [];
  featured: Product[] = [];
  readonly whatsapp = environment.store?.whatsapp || '';

  // Degradados cálidos alineados con Isablue para las tarjetas de líneas de productos.
  private gradients = [
    'linear-gradient(135deg,#c87a5b,#e0a86a)',
    'linear-gradient(135deg,#4a3a30,#7a5b45)',
    'linear-gradient(135deg,#e7b96b,#f0cf8f)',
    'linear-gradient(135deg,#8a9a7b,#aec29a)',
    'linear-gradient(135deg,#d99a7c,#f3c9a0)',
    'linear-gradient(135deg,#7a5b45,#c87a5b)'
  ];

  constructor(private home: HomeService) {}

  ngOnInit(): void {
    this.home.getCategories().subscribe({
      next: c => this.categories = c || [],
      error: () => this.categories = []
    });
    this.home.getProducts().subscribe({
      next: p => this.featured = (p || []).slice(0, 8),
      error: () => this.featured = []
    });
  }

  gradientFor(i: number): string { return this.gradients[i % this.gradients.length]; }
  onImageError(event: Event): void { (event.target as HTMLImageElement).src = 'assets/images/toy-montessori.svg'; }
}
