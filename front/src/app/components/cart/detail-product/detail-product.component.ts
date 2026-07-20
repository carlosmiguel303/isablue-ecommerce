import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { ItemCart } from 'src/app/common/item-cart';
import { CartService } from 'src/app/services/cart.service';
import { HomeService } from 'src/app/services/home.service';
@Component({selector:'app-detail-product',templateUrl:'./detail-product.component.html',styleUrls:['./detail-product.component.css']})
export class DetailProductComponent implements OnInit{
  id=0;name='';description='';price=0;urlImage='';quantity=1;loading=true;
  constructor(private home:HomeService,private route:ActivatedRoute,private cart:CartService,private toastr:ToastrService){}
  ngOnInit():void{this.route.params.subscribe(p=>{if(p['id'])this.home.getProductById(p['id']).subscribe({next:data=>{this.id=data.id;this.name=data.name;this.description=data.description;this.urlImage=data.urlImage;this.price=data.price;this.loading=false;},error:()=>this.toastr.error('No pudimos cargar este producto.')});});}
  addCart():void{this.quantity=Math.min(Math.max(Number(this.quantity)||1,1),10);this.cart.addItemCart(new ItemCart(this.id,this.name,this.quantity,this.price));this.toastr.success('Producto añadido. Tu carrito está listo.','IsaBlue');}
}
