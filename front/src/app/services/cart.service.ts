import { Injectable } from '@angular/core';
import { ItemCart } from '../common/item-cart';

@Injectable({
  providedIn: 'root'
})
export class CartService {
  private readonly storageKey = 'isablue-cart';
  private items: Map<number,ItemCart> = new Map<number, ItemCart>();

  itemList : ItemCart [] = [];



  constructor() { this.restore(); }

  addItemCart(itemCart : ItemCart){
    const current = this.items.get(itemCart.productId);
    if (current) itemCart.quantity += current.quantity;
    itemCart.quantity = Math.min(Math.max(itemCart.quantity, 1), 10);
    this.items.set(itemCart.productId, itemCart);
    this.persist();
  }

  deleteItemCart(productId:number){
    this.items.delete(productId);
    this.persist();
  }

  count(): number { return Array.from(this.items.values()).reduce((sum, item) => sum + item.quantity, 0); }
  clear(): void { this.items.clear(); this.persist(); }
  private persist(): void { localStorage.setItem(this.storageKey, JSON.stringify(Array.from(this.items.values()))); }
  private restore(): void {
    try {
      const saved = JSON.parse(localStorage.getItem(this.storageKey) || '[]');
      saved.forEach((i: any) => this.items.set(i.productId, new ItemCart(i.productId, i.productName, Number(i.quantity), Number(i.price))));
    } catch { localStorage.removeItem(this.storageKey); }
  }
  
  totalCart(){
    let totalCart:number=0;
    this.items.forEach(
      (item, clave)=>{
        totalCart+= item.getTotalPriceItem();
      }

    );
    return totalCart;
  }

  convertToListFromMap(){
    this.itemList.splice(0,this.itemList.length);
    this.items.forEach(
      (item, clave)=>{
        this.itemList.push(item);
      }

    );
    return this.itemList;
  }


}
