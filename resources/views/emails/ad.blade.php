<!DOCTYPE html>
<html lang="">

	<body>
		<a href="{{$ad->link}}" target="_blank">{{$ad->title}}</a>

		PRICE {{$ad->price}}		
		
		{!! $ad->description !!}
		
		<a href="{{$ad->link}}" target="_blank">Ad Link</a>
	</body>
</html>
