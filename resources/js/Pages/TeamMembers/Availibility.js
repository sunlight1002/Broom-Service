import React, { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import axios from "axios";
import Sidebar from "../../Admin/Layouts/Sidebar";
import AvailabilityForm from "./AvailabilityForm";

export default function Availibility() {
    const alert = useAlert();
    const param = useParams();
    const navigate = useNavigate();
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <h1 className="page-title">Availability</h1>
                <AvailabilityForm />
            </div>
        </div>
    );
}
