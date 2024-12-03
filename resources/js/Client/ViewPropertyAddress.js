import axios from "axios";
import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import 'react-phone-input-2/lib/style.css';
import { useNavigate, useParams } from "react-router-dom";
import FullPageLoader from "../../js/Components/common/FullPageLoader";
import Sidebar from "../Client/Layouts/ClientSidebar";

import {
    GoogleMap,
    LoadScript,
    InfoWindow,
    Marker,
    Autocomplete,
} from "@react-google-maps/api";
import Geocode from "react-geocode";

export default function ViewPropertyAddress({ mode }) {
    const { t } = useTranslation();
    const params = useParams();
    const alert = useAlert();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [address, setAddress] = useState({});

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const getAddress = async () => {
        try {
            const res = await axios.get(`/api/client/get-property-address/${params.id}`, { headers });
            console.log(res.data);
            
            setAddress(res.data.address); // Update to match the API response structure
        } catch (error) {
            console.error(error);
        }
    };

    useEffect(() => {
        getAddress();
    }, []);


    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title editEmployer">
                        View Property Address
                    </h1>
                    <div className="dashBox" style={{ background: "inherit", border: "none" }}>
                        <form>
                            <div className="row">
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">Address Name</label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={address.address_name}
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">Geo Address</label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={address.geo_address}
                                        />
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
}
