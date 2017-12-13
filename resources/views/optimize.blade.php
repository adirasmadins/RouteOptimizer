@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">Routes</div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="locationTextField">Location</label>
                                <input id="locationTextField" type="text" size="50">
                            </div>
                            <div class="col-md-4 build" style="margin-top: 20px;">
                                <a href="" class="btn btn-success add_to_route">Add to city to route</a>
                            </div>
                            <div class="col-md-4">
                                <label for="locationTextField">Average time for load and unload, minutes</label>
                                <input id="unloadtime" type="number" size="50">
                            </div>
                        </div>
                        <div class="row" style="margin-top: 50px">
                            <div class="col-md-3">
                                <div><span>Total Distance: </span><span id="totalDistance"></span></div>
                            </div>
                            <div class="col-md-5">
                                <div><span>Total Time: </span><span id="totalTime"></span></div>
                                <div><span>Driving Time: </span><span id="drivingTime"></span></div>
                                <div><span>Unload Time: </span><span id="unloadTime"></span></div>
                                <div><span>Stops Time: </span><span id="stopsTime"></span></div>
                            </div>
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

            var waypts = [];

            $.each(nodes, function(k, v) {
                waypts.push({
                    lat: v.lat(),
                    lng: v.lng()
                });
            });

            $.ajax({
                url: '/api/optimize',
                method: 'POST',
                contentType: "json",
                data: JSON.stringify(waypts)
            }).done(function(data) {
                $('#route-description').empty();
               initializeMap(data);
            });

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

        function initializeMap(data) {
            // Map options
            var opts = {
                center: data.cities[0],
                zoom: 6,
                mapTypeControl: false
            };

            map = new google.maps.Map(document.getElementById('map'), opts);

            var waypts = [];

            $.each(data.cities, function(k, v) {
                waypts.push({
                    location: data.cities[k],
                    stopover: true
                });
            });

            directionsService = new google.maps.DirectionsService();
            directionsDisplay = new google.maps.DirectionsRenderer({
                draggable: true,
                map: map,
                panel: document.getElementById('route-description')
            });

            computeTotalDistance(data.distance);

            computeTime(data.time, data.cities);

            // Add final route to map
            var request = {
                origin: data.cities[0],
                destination: data.cities[0],
                waypoints: waypts,
                travelMode: 'DRIVING',
                avoidHighways: false,
                provideRouteAlternatives: false,
                avoidTolls: false
            };

            console.log(request);

            directionsService.route(request, function (response, status) {
                if (status == google.maps.DirectionsStatus.OK) {
                    directionsDisplay.setDirections(response);
                }
//                clearMapMarkers();
            });
        };

        function computeTotalDistance(myTotal) {
            myTotal = (parseFloat(myTotal) / 1000).toFixed(2);

            document.getElementById('totalDistance').innerHTML = myTotal + ' km';
        }

        function computeTime(time, cities) {
            var drivingTime = time;
            var stops = 0;

            var timeForUnload = 0;

            for(var i = 1; i < cities.length; ++i) {
                timeForUnload += $('#unloadtime').val() * 60 || 0;
            }

            if(drivingTime < 9000) {
                stops += 1200;
            } else if(drivingTime < 16200) {
                stops += 2700;
            } else if(drivingTime > 16200 || drivingTime <= 32400) {
                stops += 2*2700;
            } else if(drivingTime > 32400){
                stops += 2*2700 + 324000;
            }

            console.log(drivingTime, stops, timeForUnload);

            document.getElementById('drivingTime').innerHTML = seconds2time(drivingTime);
            document.getElementById('stopsTime').innerHTML = seconds2time(stops);
            document.getElementById('unloadTime').innerHTML = seconds2time(timeForUnload);
            document.getElementById('totalTime').innerHTML = seconds2time(drivingTime + stops + timeForUnload);
        }

        google.maps.event.addDomListener(window, 'load', initAutocomplete);

        function seconds2time (seconds) {
            var hours   = Math.floor(seconds / 3600);
            var minutes = Math.floor((seconds - (hours * 3600)) / 60);
            var seconds = seconds - (hours * 3600) - (minutes * 60);
            var time = "";

            if (hours != 0) {
                time = hours+":";
            }
            if (minutes != 0 || time !== "") {
                minutes = (minutes < 10 && time !== "") ? "0"+minutes : String(minutes);
                time += minutes+":";
            }
            if (time === "") {
                time = seconds+"s";
            }
            else {
                time += (seconds < 10) ? "0"+seconds : String(seconds);
            }
            return time;
        }
    });



</script>