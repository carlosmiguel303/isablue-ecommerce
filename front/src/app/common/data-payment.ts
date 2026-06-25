export class DataPayment {
  constructor(
    public method: string,
    public currency: string,
    public description: string,
    public orderId: number
  ) {}
}
