<!DOCTYPE html>
<html lang="">

	<body>
		@foreach( $ads as $ad)
			<h3><a href="{{$ad->link}}" target="_blank">{{$ad->title}}</a></h3>

			<p>PRICE {{$ad->price}}</p>		
			
			{!!$ad->description!!}

			<hr/>
		@endforeach

		<p>Jijiki - Copyright Pancakes and Cookies 2017</p>		
		
	</body>
</html>
