import axios from "axios";
import React, { useState, useEffect } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function Discount() {
    const { t, i18n } = useTranslation();
    const [formValues, setFormValues] = useState({
        type: "",
        value: "",
    });
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState(null);

    const alert = useAlert();
    const navigate = useNavigate();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getDiscount = () => {
        axios.get(`/api/admin/get-discount`, { headers }).then((res) => {
            setFormValues({
                type: res.data?.discount_type,
                value: res.data?.discount_value,
            });
        });
    };

    useEffect(() => {
        getDiscount();
    }, [])


    const handleSubmit = (e) => {
        e.preventDefault();
        setLoading(true);

        const data = {
            type: formValues.type,
            value: formValues.value
        };

        axios
            .post(`/api/admin/add-discount`, data, { headers })
            .then((res) => {
                if (res.data.errors) {
                    setLoading(false);
                    setErrors(res.data.errors);
                    for (let e in res.data.errors) {
                        alert.error(res.data.errors[e]);
                    }
                } else {
                    setLoading(false);
                    getDiscount();
                    alert.success(res.data.message);
                }
            })
            .catch((error) => {
                console.log(error);
                
                setLoading(false);
                alert.error("An unexpected error occurred.");
            });
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <h1 className="page-title">{t("admin.global.discount")}</h1>
                <form onSubmit={handleSubmit}>
                    <div className="row">
                        <div className="col-lg-6 col-12">
                            <div className="dashBox p-0 p-md-4">
                                <div className="form-group">
                                    <label className="control-label">{t("global.Type")}</label>
                                    <select
                                        className="form-control"
                                        value={formValues.type}
                                        onChange={(e) => setFormValues({
                                            ...formValues,
                                            type: e.target.value,
                                        })}
                                    >
                                        <option value="">{t("worker.settings.pleaseSelect")}</option>
                                        <option value="fixed">{t("worker.settings.fixed")}</option>
                                        <option value="percentage">{t("admin.global.percentage")}</option>
                                    </select>
                                </div>
                                {
                                    formValues.type === "fixed" ? (
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("admin.global.amount")}
                                            </label>
                                            <input
                                                type="number"
                                                min="0"
                                                className="form-control"
                                                value={formValues.value}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        value: e.target.value,
                                                    })
                                                }}
                                                placeholder="Enter amount"
                                                required
                                            />
                                        </div>
                                    ) : (
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("admin.global.percentage")}
                                            </label>
                                            <input
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                className="form-control"
                                                value={formValues.value}
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        value: e.target.value,
                                                    })
                                                }}
                                                placeholder="Enter percentage"
                                                required
                                            />
                                        </div>
                                    )
                                }

                                <button type="submit" className="btn btn-primary">
                                    {t("global.update")}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                {loading && <FullPageLoader />}
            </div>
        </div>
    );
}
