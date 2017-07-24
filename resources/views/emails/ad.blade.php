<!DOCTYPE html>
<html lang="">

	<body>
		<h3><a href="{{$ad->link}}" target="_blank">{{$ad->title}}</a></h3>
		

		<p>PRICE {{$ad->price}}</p>		
		
		<p>{!! $ad->description !!}</p>
		
		<br />
		<a href="{{$ad->link}}" target="_blank">Ad Link</a>
	</body>
</html>
