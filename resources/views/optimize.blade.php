@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">Routes</div>
                    <div class="panel-body">
                        <div class="row"></div>
                        <div class="col-md-5">
                            <label for="locationTextField">Location</label>
                            <input id="locationTextField" type="text" size="50">
                        </div>
                        <div class="col-md-2">
                            <a href="" class="btn btn-success add_to_route">Add to city to route</a>
                        </div>
                        <div class="col-md-2 build">

                        </div>
                        <div class="col-md-3">
                            <span>Total Distance: </span><span id="total"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <table class="table table-hover" id="items-list">
                    <thead>
                    <tr>
                        <th>Address</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>

            <div class="col-md-12">
                <div id="map" style="clear:both; height:500px;">

                </div>
            </div>

            <div class="col-md-12" style="height: 500px; margin-top: 50px;">
                <div id="route-description"></div>
            </div>
        </div>
    </div>
@endsection
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.0.5/sweetalert2.min.js"></script>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBw5IpBRVkomiSv7E-Wlw_OiZiZ6AyVVkI&libraries=places"></script>

<script>
    $(function () {

        var markers = [];
        var durations = [];
        var directionsDisplay = null;
        var directionsService;
        var polylinePath;

        let nodes = {};
        let autocomplete = document.getElementById('locationTextField');

        $('.add_to_route').on('click', function (e) {
           e.preventDefault();

            if(!autocomplete.getPlace()) {
                swal(
                    'Oops...',
                    'Place not seelcted',
                    'error'
                );
                return;
            }

            if(autocomplete.getPlace().place_id in nodes) {
                swal(
                    'Oops...',
                    'This place is already in route',
                    'error'
                );
                return;
            }


           nodes[autocomplete.getPlace().place_id] = autocomplete.getPlace().geometry.location;

            if(Object.keys(nodes).length >= 2 && !$('.build-route').length) {
                $('.build').append(
                    `<a href="javascript:void(0)" class="btn btn-warning build-route">Build route</a>`
                );
            }

           $('#items-list').find('tbody').append(
               `<tr>
                    <td>${autocomplete.getPlace().formatted_address}</td>
                    <td><a href="javascript:void(0)" class="delete-item" data-id="${autocomplete.getPlace().place_id}"><i class="fa fa-trash"></i></a></td>
                </tr>`
           );

            autocomplete.set('place',void(0));
            $('#locationTextField').val('');
        });

        $(document).on('click', '.delete-item', function (e) {
            e.preventDefault();

            let place_id = $(this).attr('data-id');

            $(this).parent().parent().remove();

            delete nodes[place_id];

            if(Object.keys(nodes).length >= 2 && !$('.build-route').length) {
                $('.build').append(
                    `<a href="javascript:void(0)" class="btn btn-warning build-route">Build route</a>`
                );
            }

            if(Object.keys(nodes).length < 2 && $('.build-route').length) {
                $('.build-route').remove();
            }
        });

        $(document).on('click', '.build-route', function (e) {
            e.preventDefault();

            initializeMap();

            $(this).remove();
        });

        function initAutocomplete() {
            // Create the autocomplete object, restricting the search to geographical
            // location types.
            autocomplete = new google.maps.places.Autocomplete(
                /** @type {!HTMLInputElement} */(autocomplete),
                {types: ['geocode']});

            // When the user selects an address from the dropdown, populate the address
            // fields in the form.
        }

        function initializeMap() {
            // Map options
            var opts = {
                center: {lat: nodes[Object.keys(nodes)[0]].lat(), lng: nodes[Object.keys(nodes)[0]].lng()},
                zoom: 6,
                mapTypeControl: false
            };

            map = new google.maps.Map(document.getElementById('map'), opts);

            var waypts = [];

            $.each(nodes, function(k, v) {
                var marker = new google.maps.Marker({
                    position: {lat: v.lat(), lng: v.lng()},
                    map: map
                });

                waypts.push({
                    location: {lat: v.lat(), lng: v.lng()},
                    stopover: true
                });
            });

            directionsService = new google.maps.DirectionsService();
            directionsDisplay = new google.maps.DirectionsRenderer({
                draggable: true,
                map: map,
                panel: document.getElementById('route-description')
            });

            directionsDisplay.addListener('directions_changed', function() {
                computeTotalDistance(directionsDisplay.getDirections());
            });

            // Add final route to map
            var request = {
                origin: {lat: nodes[Object.keys(nodes)[0]].lat(), lng: nodes[Object.keys(nodes)[0]].lng()},
                destination: {lat: nodes[Object.keys(nodes)[0]].lat(), lng: nodes[Object.keys(nodes)[0]].lng()},
                waypoints: waypts,
                travelMode: 'DRIVING',
                avoidHighways: false,
                provideRouteAlternatives: false,
                optimizeWaypoints: true,
                avoidTolls: false
            };

            directionsService.route(request, function (response, status) {
                if (status == google.maps.DirectionsStatus.OK) {
                    directionsDisplay.setDirections(response);
                }
//                clearMapMarkers();
            });
        };

        function computeTotalDistance(result) {
            var total = 0;
            var myroute = result.routes[0];
            for (var i = 0; i < myroute.legs.length; i++) {
                total += myroute.legs[i].distance.value;
            }
            total = total / 1000;
            document.getElementById('total').innerHTML = total + ' km';
        }

        google.maps.event.addDomListener(window, 'load', initAutocomplete);
    });



</script>