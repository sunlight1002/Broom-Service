import { useRef, useState, useEffect, memo } from "react";
import {
    GoogleMap,
    LoadScript,
    InfoWindow,
    Marker,
    Autocomplete,
} from "@react-google-maps/api";
import Geocode from "react-geocode";

const containerStyle = {
    width: "100%",
    height: "300px",
};
const Map = memo(function Map({
    onLoad,
    onPlaceChanged,
    latitude,
    longitude,
    address,
    setLatitude,
    setLongitude,
    libraries,
    place,
}) {
    let addressSearchRef = useRef();
    Geocode.setApiKey("AIzaSyBva3Ymax7XLY17ytw_rqRHggZmqegMBuM");

    const center = {
        lat: latitude,
        lng: longitude,
    };
    useEffect(() => {
        if (address === "" && place) {
            addressSearchRef.current && (addressSearchRef.current.value = "");
            setLatitude(32.109333);
            setLongitude(34.855499);
        }
    }, [address]);

    return (
        <div className="form-group">
            <label className="control-label">Enter a location</label>
            <LoadScript
                googleMapsApiKey="AIzaSyBva3Ymax7XLY17ytw_rqRHggZmqegMBuM"
                libraries={libraries}
            >
                <GoogleMap
                    mapContainerStyle={containerStyle}
                    center={center}
                    zoom={15}
                >
                    <Marker
                        draggable={true}
                        onDragEnd={(e) => onMarkerDragEnd(e)}
                        position={{
                            lat: latitude,
                            lng: longitude,
                        }}
                    />
                    {address ? (
                        <InfoWindow
                            onClose={(e) => onInfoWindowClose(e)}
                            position={{
                                lat: latitude + 0.0018,
                                lng: longitude,
                            }}
                        >
                            <div>
                                <span
                                    style={{
                                        padding: 0,
                                        margin: 0,
                                    }}
                                >
                                    {address}
                                </span>
                            </div>
                        </InfoWindow>
                    ) : (
                        <></>
                    )}
                    <Marker />
                </GoogleMap>
                <Autocomplete onPlaceChanged={onPlaceChanged} onLoad={onLoad}>
                    <input
                        ref={addressSearchRef}
                        type="text"
                        placeholder="Search your address"
                        className="form-control mt-1"
                    />
                </Autocomplete>
            </LoadScript>
        </div>
    );
});

export default Map;
