import React, { useState, useEffect } from "react";
import { Link, useNavigate , useParams} from "react-router-dom";
import axios from "axios";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import { Base64 } from "js-base64";
import Sidebar from "../../Layouts/Sidebar";
import useWindowWidth from "../../../Hooks/useWindowWidth";

export default function Templates() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const params = useParams();
    const windowWidth = useWindowWidth();
    const [show, setShow] = useState(false)
    
    const [templates, setTemplates] = useState({
        key:"",
        message_heb: "",
        message_en: "",
        message_spa: "",
        message_rus: "",
    });

    useEffect(() => {
        if (windowWidth < 768) {
            setShow(true)
        } else {
            setShow(false)
        }
    }, [windowWidth])    

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleGetTemplates = async () => {
        try {
            const response = await axios.get(`/api/admin/whatsapp-templates/${Base64.decode(params.id)}`, { headers });
            const res = response?.data;
            setTemplates({
                key: res.key,
                message_heb: res.message_heb,
                message_en: res.message_en,
                message_spa: res.message_spa,
                message_rus: res.message_rus,
            })
            
        } catch (error) {
            console.log(error.response ? error.response.data : error.message);
        }
    };

    useEffect(() => {
        handleGetTemplates();
    }, [])

    const handleChange = (language) => (event) => {
        setTemplates({
            ...templates,
            [language]: event.target.value,
        });
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        try {
            const response = await axios.put(`/api/admin/whatsapp-templates/${Base64.decode(params.id)}`, templates, { headers });
            handleGetTemplates()
         
            if (response.status === 200) {
                Swal.fire({
                icon: 'success',
                title: response.data.message,
                // text: response.data.message,
            });
            }
            
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: error.response?.data?.message || 'An error occurred while saving templates.',
            });
        }
    };

    const suggestions = [
        "{firstname} :use this for client/worker first name" ,
         "{lastname} :use this for client/worker last name",
        "{Change_Service_Date} :use this for change service date link",
        "{Cancel_Service} :use this for cancel service link",
        "{holidays} :use this for holiday date",
    ];

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">{templates?.key}</h1>
                        </div>
                    </div>
                </div>
                <div className="dashBox" style={{ backgroundColor: "inherit", border: "none" }}>
                    <form onSubmit={handleSubmit} className={`d-flex ${show?'flex-wrap-reverse':'nowrap'}`}>
                        <div className="flex-grow-1 me-4  w-100 mr-3 mt-3">
                            <div className="form-group">
                                <label htmlFor="hebrew">Hebrew</label>
                                <textarea
                                    id="message_heb"
                                    className="form-control"
                                    maxLength={1000}
                                    rows="5"
                                    value={templates.message_heb}
                                    onChange={handleChange('message_heb')}
                                />
                            </div>
                            <div className="form-group">
                                <label htmlFor="english">English</label>
                                <textarea
                                    id="message_en"
                                    className="form-control"
                                    maxLength={1000}
                                    rows="5"
                                    value={templates.message_en}
                                    onChange={handleChange('message_en')}
                                />
                            </div>
                            <div className="form-group">
                                <label htmlFor="spanish">Spanish</label>
                                <textarea
                                    id="message_spa"
                                    className="form-control"
                                    maxLength={1000}
                                    rows="5"
                                    value={templates.message_spa}
                                    onChange={handleChange('message_spa')}
                                />
                            </div>
                            <div className="form-group">
                                <label htmlFor="russian">Russian</label>
                                <textarea
                                    id="message_rus"
                                    className="form-control"
                                    maxLength={1000}
                                    rows="5"
                                    value={templates.message_rus}
                                    onChange={handleChange('message_rus')}
                                />
                            </div>
                            <button type="submit" className="btn btn-primary mt-3">Save Templates</button>
                        </div>
                        <div className="suggestions-box" >
                            <h5 className="mb-3">Suggestions</h5>
                            <ul className="list-group" id="ko" style={{maxHeight: "72vh", overflowX: "auto"}}>
                                {suggestions.map((suggestion, index) => (
                                    <li key={index} className="list-group-item" style={{width: "300px"}}>
                                        {suggestion}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
