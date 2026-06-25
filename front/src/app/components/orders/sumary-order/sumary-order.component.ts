import { Component, OnInit } from '@angular/core';
import { DataPayment } from 'src/app/common/data-payment';
import { ItemCart } from 'src/app/common/item-cart';
import { Order } from 'src/app/common/order';
import { OrderProduct } from 'src/app/common/order-product';
import { OrderState } from 'src/app/common/order-state';
import { CartService } from 'src/app/services/cart.service';
import { OrderService } from 'src/app/services/order.service';
import { PaymentService } from 'src/app/services/payment.service';
import { SessionStorageService } from 'src/app/services/session-storage.service';
import { UserService } from 'src/app/services/user.service';

@Component({
  selector: 'app-sumary-order',
  templateUrl: './sumary-order.component.html',
  styleUrls: ['./sumary-order.component.css']
})
export class SumaryOrderComponent implements OnInit {

  items : ItemCart [] = [];
  totalCart : number =0;
  firstName : string = '';
  lastName : string = '';
  email : string = '';
  address : string ='';
  orderProducts:OrderProduct [] = [];
  userId : number =0;

  constructor(private cartService:CartService, 
    private userService:UserService, 
    private orderService:OrderService, 
    private paymentService:PaymentService,
    private sessionStorage:SessionStorageService
    ){}


  ngOnInit(): void {
    console.log('ngOnInit');
    this.items = this.cartService.convertToListFromMap();
    this.totalCart = this.cartService.totalCart();
    this.userId = this.sessionStorage.getItem('token').id;
    this.getCurrentUser();
  }

  generateOrder(){
    this.orderProducts = this.items.map(
      item => new OrderProduct(null, item.productId, item.quantity, item.price)
    );

    const order = new Order(null, new Date(), this.orderProducts, this.userId, OrderState.CANCELLED);

    this.orderService.createOrder(order).subscribe({
      next: createdOrder => {
        this.sessionStorage.setItem('order', createdOrder);
        const payment = new DataPayment('paypal', 'USD', 'COMPRA ISABLUE', createdOrder.id!);

        this.paymentService.getUrlPaypalPayment(payment).subscribe({
          next: response => window.location.href = response.url,
          error: error => console.error('No se pudo iniciar el pago', error)
        });
      },
      error: error => console.error('No se pudo crear la orden', error)
    });
  }


  deleteItemCart(productId:number){
    this.cartService.deleteItemCart(productId);
    this.items = this.cartService.convertToListFromMap();
    this.totalCart = this.cartService.totalCart();
  }

  getCurrentUser(){
    this.userService.getCurrentUser().subscribe(
      data => {
        this.firstName = data.firstName;
        this.lastName = data.lastName;
        this.email = data.email;
        this.address = data.address;
      }
    );
  }

}
