<article class="review-card">
    <span class="stars" aria-label="{{ $review->rating }} de 5 estrellas">{{ $review->stars }}</span>
    @if($review->photo)<img class="review-photo" src="{{ $review->photo }}" alt="Foto de {{ $review->author_name }}" loading="lazy">@endif
    <p>“{{ $review->comment }}”</p>
    @if($showProduct && $review->product)<a class="review-product" href="/juguete/{{ $review->product->slug }}"><img src="{{ $review->product->image }}" alt="" loading="lazy">{{ $review->product->name }}</a>@endif
    <footer><strong>{{ $review->author_name }}@if($review->district) · {{ $review->district }}@endif</strong><span class="verified">✓ Compra verificada</span></footer>
</article>
