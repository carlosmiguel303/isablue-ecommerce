import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { ItemCart } from 'src/app/common/item-cart';
import { Product } from 'src/app/common/product';
import { CartService } from 'src/app/services/cart.service';
import { HomeService } from 'src/app/services/home.service';

interface Category { id: number; name: string; }

@Component({ selector: 'app-home', templateUrl: './home.component.html', styleUrls: ['./home.component.css'] })
export class HomeComponent implements OnInit {
  products: Product[] = [];
  filteredProducts: Product[] = [];
  categories: Category[] = [{ id: 0, name: 'Todos los productos' }];
  selectedCategory = 0;
  term = '';

  private demoCategories: Category[] = [
    { id: 1, name: 'Juguetes didácticos' },
    { id: 2, name: 'Bebés (0–12 meses)' },
    { id: 3, name: 'Arte y creatividad' },
    { id: 4, name: 'Vehículos' },
    { id: 5, name: 'Dinosaurios' }
  ];

  constructor(
    private homeService: HomeService,
    private route: ActivatedRoute,
    private cartService: CartService,
    private toastr: ToastrService
  ) {}

  ngOnInit(): void {
    this.homeService.getProducts().subscribe({
      next: data => { this.products = data && data.length ? data : this.demoProducts(); this.apply(); },
      error: () => { this.products = this.demoProducts(); this.apply(); }
    });
    this.homeService.getCategories().subscribe({
      next: cats => { this.categories = [{ id: 0, name: 'Todos los productos' }, ...(cats && cats.length ? cats : this.demoCategories)]; },
      error: () => { this.categories = [{ id: 0, name: 'Todos los productos' }, ...this.demoCategories]; }
    });
    this.route.queryParamMap.subscribe(params => { this.term = params.get('q') || ''; this.apply(); });
  }

  selectCategory(id: number): void { this.selectedCategory = id; this.apply(); }

  categoryName(id: any): string {
    const c = this.categories.find(x => x.id === Number(id));
    return c ? c.name : 'Isablue';
  }

  countByCategory(id: number): number {
    if (id === 0) return this.products.length;
    return this.products.filter(p => Number(p.categoryId) === id).length;
  }

  private apply(): void {
    const t = this.term.trim().toLowerCase();
    this.filteredProducts = this.products.filter(p => {
      const matchesCat = this.selectedCategory === 0 || Number(p.categoryId) === this.selectedCategory;
      const matchesTerm = !t || `${p.name} ${p.code} ${p.description}`.toLowerCase().includes(t);
      return matchesCat && matchesTerm;
    });
  }

  addToCart(p: Product): void {
    this.cartService.addItemCart(new ItemCart(p.id, p.name, 1, p.price));
    this.toastr.success(`${p.name} se agregó al carrito`, 'Añadido');
  }

  onImageError(event: Event): void { (event.target as HTMLImageElement).src = 'assets/images/toy-montessori.svg'; }

  demoProducts(): Product[] {
    return [
      new Product(1, 'Set Montessori de Madera', 'ISA-MONT-001', 'Juguete didáctico para coordinación, colores y motricidad fina.', 89, 'assets/images/toy-montessori.svg', null as any, '1', '1'),
      new Product(2, 'Rompecabezas Infantil Animales', 'ISA-PUZ-002', 'Puzzle educativo para estimular memoria, concentración y paciencia.', 45, 'assets/images/toy-puzzle.svg', null as any, '1', '1'),
      new Product(3, 'Carrito Didáctico de Madera', 'ISA-CAR-003', 'Vehículo seguro y resistente para juego imaginativo.', 59, 'assets/images/toy-car.svg', null as any, '1', '4'),
      new Product(4, 'Osito Sensorial Bebé', 'ISA-BEBE-004', 'Peluche suave para estimulación temprana y apego seguro.', 69, 'assets/images/oso.png', null as any, '1', '2'),
      new Product(5, 'Bloques de Construcción Creativa', 'ISA-BLOCK-005', 'Piezas coloridas para creatividad, lógica y trabajo en equipo.', 79, 'assets/images/toy-blocks.svg', null as any, '1', '3'),
      new Product(6, 'Dinosaurio Educativo', 'ISA-DINO-006', 'Figura infantil para aprender jugando y desarrollar imaginación.', 55, 'assets/images/toy-dino.svg', null as any, '1', '5')
    ];
  }
}
