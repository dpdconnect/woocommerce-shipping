jQuery(document).ready(function($){
    let infowindows = [];
    let markers = [];
    let coordinates;
    let parcelshops;
    const styledMapType = new google.maps.StyledMapType(
	[
	    {
		"elementType": "geometry",
		"stylers": [
		    {
			"color": "#f5f5f5"
		    }
		]
	    },
	    {
		"elementType": "labels.icon",
		"stylers": [
		    {
			"visibility": "off"
		    }
		]
	    },
	    {
		"elementType": "labels.text.fill",
		"stylers": [
		    {
			"color": "#616161"
		    }
		]
	    },
	    {
		"elementType": "labels.text.stroke",
		"stylers": [
		    {
			"color": "#f5f5f5"
		    }
		]
	    },
	    {
		"featureType": "administrative.land_parcel",
		"elementType": "labels.text.fill",
		"stylers": [
		    {
			"color": "#bdbdbd"
		    }
		]
	    },
	    {
		"featureType": "poi",
		"elementType": "geometry",
		"stylers": [
		    {
			"color": "#eeeeee"
		    }
		]
	    },
	    {
		"featureType": "poi",
		"elementType": "labels.text.fill",
		"stylers": [
		    {
			"color": "#757575"
		    }
		]
	    },
	    {
		"featureType": "poi.park",
		"elementType": "geometry",
		"stylers": [
		    {
			"color": "#e5e5e5"
		    }
		]
	    },
	    {
		"featureType": "poi.park",
		"elementType": "labels.text.fill",
		"stylers": [
		    {
			"color": "#9e9e9e"
		    }
		]
	    },
	    {
		"featureType": "road",
		"elementType": "geometry",
		"stylers": [
		    {
			"color": "#ffffff"
		    }
		]
	    },
	    {
		"featureType": "road.arterial",
		"elementType": "labels.text.fill",
		"stylers": [
		    {
			"color": "#757575"
		    }
		]
	    },
	    {
		"featureType": "road.highway",
		"elementType": "geometry",
		"stylers": [
		    {
			"color": "#dadada"
		    }
		]
	    },
	    {
		"featureType": "road.highway",
		"elementType": "labels.text.fill",
		"stylers": [
		    {
			"color": "#616161"
		    }
		]
	    },
	    {
		"featureType": "road.local",
		"elementType": "labels.text.fill",
		"stylers": [
		    {
			"color": "#9e9e9e"
		    }
		]
	    },
	    {
		"featureType": "transit.line",
		"elementType": "geometry",
		"stylers": [
		    {
			"color": "#e5e5e5"
		    }
		]
	    },
	    {
		"featureType": "transit.station",
		"elementType": "geometry",
		"stylers": [
		    {
			"color": "#eeeeee"
		    }
		]
	    },
	    {
		"featureType": "water",
		"elementType": "geometry",
		"stylers": [
		    {
			"color": "#d2e4f3"
		    }
		]
	    },
	    {
		"featureType": "water",
		"elementType": "labels.text.fill",
		"stylers": [
		    {
			"color": "#9e9e9e"
		    }
		]
	    }
	],
	{name: 'Styled Map'});

    const checkForAddress = function(gMap){
	if( typeof addMarkers == "function" ){
	    addMarkers(gMap);
	} else {
	    // Address not found or API invalid.
            console.log( 'Gmaps did could not find address / DPD API is not connected.' );
            $('#overlay').hide();
            $('#map').hide();

            $('.address_error').show();
            $('.openDPDParcelMap').hide();            
        }
    }

    const addMarkers = function (gMap) {
        const icon = new google.maps.MarkerImage(
            '/wp-content/plugins/dpdconnect/assets/images/pickup.png',
            new google.maps.Size(57, 62), new google.maps.Point(0, 0), new google.maps.Point(0, 31)
        );

        // Parcelshops are set as variable through wp_localize_script
        parcelshops.forEach(parcelshop => {
            const marker = new google.maps.Marker({
                map: gMap,
                position: new google.maps.LatLng(parcelshop.latitude, parcelshop.longitude),
                icon: icon,
            });
	    const infowindow = new google.maps.InfoWindow();
	    const content = "\
		<img src='/wp-content/plugins/dpdconnect/assets/images/pickup.png'/> \
		<strong class='modal-title'>" + parcelshop.company + "</strong><br/> \
		" + parcelshop.street + " " + parcelshop.houseNo + "<br/> " + parcelshop.zipCode + " " + parcelshop.city + "</br> \
	    ";
	    let openingshours = "";

	    for (let i = 0; i < parcelshop.openingHours.length; i++) {
                let openingDescription = parcelshop.openingHours[i].openMorning + " - " + parcelshop.openingHours[i].closeMorning + " " + parcelshop.openingHours[i].openAfternoon + " - " + parcelshop.openingHours[i].closeAfternoon;
                if (openingDescription === '00:00 - 00:00 00:00 - 00:00') {
                    openingDescription = translations.closed;
                }

		openingshours = openingshours + "\
                    <div class='modal-week-row'> \
                        <strong class='modal-day'>" + parcelshop.openingHours[i].weekday + "</strong>" + " " + "\
                        <p>" + openingDescription + "</p>\
                    </div>";
	    }

            const link = "<a id='" + parcelshop.parcelShopId + "' class='parcelLink button'>" + translations.shipTo + "</a>"
	    infowindow.setContent(
		"<div class='info-modal-content'>" +
                content +
		"<strong class='modal-link'>" +
                link +
                "</strong> " +
		openingshours +
		"</div>"
	    );

	    infowindows.push(infowindow);

	    google.maps.event.addListener(marker, 'click', (function (marker) {
		return function () {
		    infowindow.open(gMap, marker);
		}
	    })(marker));

	    markers.push(marker);

	    $("alert alert-danger").hide();
	});
    }

    const showMap = function()
    {
        const html = '\
            <div id="parcelshops">\
                <div id="map"></div> \
            </div>';

        $('.mapContainer').html(html);
    };

    const initMap = function (map) {
        const gMap = new google.maps.Map(map, {
            zoom: 10,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            center: new google.maps.LatLng(coordinates.latitude, coordinates.longitude),
	    mapTypeControlOptions: {
		mapTypeIds: ['styled_map']
	    }
        });

        checkForAddress(gMap);
    }


    $('body').on('click', '.openDPDParcelMap, .otherDPDParcel', function(e) {
        e.preventDefault();
        $.ajax({
            type : 'get',
            url: hook.url,
            data: {
                action : 'dpdconnect_pickup_data',
                nonce: hook.nonce,
                postId: hook.postId,
            },
            success: function(data) {
                if (data.success === false) {
                    $('.parcel-notice').show();
                    $('.parcel-notice').html('<div class="notice notice-error"><p>'+ data.data +'</p></div>');
                    return;
                }
                $('.parcel-notice').hide();
                const parsed = JSON.parse(data);
                coordinates = parsed.coordinates;
                parcelshops = parsed.parcelshops;
                showMap();
                $('#map').show();
                hideMapButton();
                initMap( $('#map')[0] );
            },
        });
    });

    const addChosen = function (parcelId) {
        parcelshop = parcelshops.find(function(e) {
            return e.parcelShopId == parcelId;
        });

        let selectedParcelShop = '\
            <ul class="selected-parcelshop">\
                <li>\
                    <div class="sidebar_single">\
                        <strong class="company">' + parcelshop.company + '</strong> <br> \
                        <span class="address">' + parcelshop.street + ' ' + parcelshop.houseNo + '</span> <br /> \
                        <span class="address ">' + parcelshop.zipCode + ' ' + parcelshop.city + '</span> <br /> \
                    </div> \
                </li> \
            </ul>';
        $('body').find('#selectedParcelShop').html(selectedParcelShop);

        $.ajax({
            type : "post",
            dataType : "json",
            url : hook.url,
            data : {
                action: "select_parcelshop",
                nonce: hook.nonce,
                postId: hook.postId,
                parcelshopId: parcelshop.parcelShopId,
            }
        })
    }

    $('body').on('click', '.parcelLink', function(e) {
        addChosen(this.id);
        hideMap();
        showMapButton();
        changeSelectButtonText();
    });

    function hideMapButton() {
        $('.openDPDParcelMap').hide();
    }

    function showMapButton() {
        $('.openDPDParcelMap').show();
    }

    function hideMap() {
        $('.mapContainer').find('#parcelshops').hide();
    }

    function changeSelectButtonText() {
        $('.openDPDParcelMap').html(translations.changeTo);
    }

    $('body').on('click', '#overlay', function(){
        $('#overlay').hide();
    });

    $('body').on('click', '#map', function(e){
        e.stopPropagation();
    });
})
