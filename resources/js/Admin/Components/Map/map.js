import { useRef, useState, useEffect, memo } from "react";
import {
    GoogleMap,
    LoadScript,
    InfoWindow,
    Marker,
    Autocomplete,
} from "@react-google-maps/api";
import Geocode from "react-geocode";
import { useTranslation } from "react-i18next";

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
    language,
}) {
    console.log(language);
    
    let addressSearchRef = useRef();
    const { t } = useTranslation();
    Geocode.setApiKey("AIzaSyBU01s3r8ER0qJd1jG0NA8itmcNe-iSTYk");

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
            <label className="control-label">
                {t("admin.global.location")}
            </label>
            <LoadScript
                googleMapsApiKey="AIzaSyBU01s3r8ER0qJd1jG0NA8itmcNe-iSTYk"
                libraries={libraries}
            >
                <div className="skyBorder">

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
                </div>
                <div className="mt-3">
                <Autocomplete onPlaceChanged={onPlaceChanged} onLoad={onLoad}>
                    <input
                        ref={addressSearchRef}
                        type="text"
                        placeholder={t("admin.global.locationPlaceholder")}
                        className="form-control mt-1 skyBorder"
                        />
                </Autocomplete>
                </div>
            </LoadScript>
        </div>
    );
});

export default Map;
