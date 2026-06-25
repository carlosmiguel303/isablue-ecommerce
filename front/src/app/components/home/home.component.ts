import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Product } from 'src/app/common/product';
import { HomeService } from 'src/app/services/home.service';

@Component({ selector: 'app-home', templateUrl: './home.component.html', styleUrls: ['./home.component.css'] })
export class HomeComponent implements OnInit{
  products: Product [] = [];
  filteredProducts: Product [] = [];
  constructor(private homeService:HomeService, private route: ActivatedRoute){}
  ngOnInit(): void {
    this.homeService.getProducts().subscribe({
      next: data => { this.products = data && data.length ? data : this.demoProducts(); this.applyFilter(this.route.snapshot.queryParamMap.get('q') || ''); },
      error: () => { this.products = this.demoProducts(); this.applyFilter(''); }
    });
    this.route.queryParamMap.subscribe(params => this.applyFilter(params.get('q') || ''));
  }
  applyFilter(q:string): void {
    const term = q.trim().toLowerCase();
    this.filteredProducts = !term ? this.products : this.products.filter(p => `${p.name} ${p.code} ${p.description}`.toLowerCase().includes(term));
  }
  onImageError(event: Event): void { (event.target as HTMLImageElement).src = 'assets/images/toy-teddy.svg'; }
  demoProducts(): Product[] { return [
    new Product(1,'Set Montessori de Madera','ISA-MONT-001','Juguete didáctico para coordinación, colores y motricidad fina.',89,'assets/images/toy-montessori.svg',null as any,'1','1'),
    new Product(2,'Rompecabezas Infantil Animales','ISA-PUZ-002','Puzzle educativo para estimular memoria, concentración y paciencia.',45,'assets/images/toy-puzzle.svg',null as any,'1','2'),
    new Product(3,'Carrito Didáctico de Madera','ISA-CAR-003','Vehículo seguro y resistente para juego imaginativo.',59,'assets/images/toy-car.svg',null as any,'1','3'),
    new Product(4,'Osito Sensorial Bebé','ISA-BEBE-004','Peluche suave para estimulación temprana y apego seguro.',69,'assets/images/toy-teddy.svg',null as any,'1','4'),
    new Product(5,'Bloques de Construcción Creativa','ISA-BLOCK-005','Piezas coloridas para creatividad, lógica y trabajo en equipo.',79,'assets/images/toy-blocks.svg',null as any,'1','1'),
    new Product(6,'Dinosaurio Educativo','ISA-DINO-006','Figura infantil para aprender jugando y desarrollar imaginación.',55,'assets/images/toy-dino.svg',null as any,'1','5')
  ];}
}
