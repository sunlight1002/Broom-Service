import i18next from "i18next";
import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import axios from "axios";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import PhoneInput from 'react-phone-input-2';
import 'react-phone-input-2/lib/style.css';
import styled from 'styled-components';

const StyledPhoneInput = styled(PhoneInput)`
.form-control {
    width: 100%;
    max-width: 100%;
}
`;

export default function Acc() {
    const { t } = useTranslation();
    const [name, setName] = useState("");
    const [address, setAddress] = useState("");
    const [email, setEmail] = useState("");
    const [file, setFile] = useState("");
    const [color, setColor] = useState("");
    const [phone, setPhone] = useState("");
    const [avatar, setAvatar] = useState("");
    const [lng, setLng] = useState("");
    const [errors, setErrors] = useState([]);
    const [loading, setLoading] = useState(false);
    const [twostepverification, setTwostepverification] = useState(false);
    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleChange = (e) => {
        setFile(URL.createObjectURL(e.target.files[0]));
        setAvatar(e.target.files[0]);
    };

    const getSetting = async () => {

        try {
            setLoading(true);
            const response = await axios.get("/api/admin/my-account", { headers });
            
            setName(response.data.account.name);
            setColor(response.data.account.color);
            setEmail(response.data.account.email);
            setPhone(response.data.account.phone);
            setLng(response.data.account.lng);
            setAddress(response.data.account.address);
            setFile(response.data.account.avatar);
            setTwostepverification(response.data.account.two_factor_enabled === 1);
            setLoading(false)
        } catch (error) {
            setLoading(false)
            console.error("Error fetching settings:", error);
        }
    };

    useEffect(() => {
        getSetting();
    }, []);

    const handleSubmit = async (e) => {
        location.reload();
        setLoading(true);
        const formData = new FormData();
        formData.append("name", name);
        formData.append("email", email);
        formData.append("address", address);
        formData.append("color", color);
        formData.append("avatar", avatar);
        formData.append("phone", phone);
        formData.append("twostepverification", twostepverification);
        formData.append("lng", lng === "0" ? "heb" : lng);
        i18next.changeLanguage(lng);

        // for (const [key, value] of formData.entries()) {
        //     console.log(`${key}: ${value}`);
        // }

        try {
            const response = await axios.post(`/api/admin/my-account`, formData, { headers });
            if (response.data.errors) {
                setLoading(false);
                setErrors(response.data.errors);
            } else {
                localStorage.setItem("admin-lng", lng)
                setLoading(false);
                alert.success("Account details have been updated successfully");
            }
        } catch (error) {
            setLoading(false);
            console.error("Error updating account:", error);
        }
    };

    return (
        <div className="card" style={{boxShadow: "none"}}>
            <div className="card-body">
                <form>
                    <div className="form-group">
                        <label className="control-label">{t("admin.sidebar.settings.myName")}</label>
                        <input
                            type="text"
                            className="form-control"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            placeholder={t("admin.sidebar.settings.myName")}
                        />
                    </div>
                    <div className="form-group">
                        <label className="control-label">{t("admin.sidebar.settings.myEmail")}</label>
                        <input
                            type="text"
                            className="form-control"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder={t("admin.sidebar.settings.myEmail")}
                        />
                        {errors.email && (
                            <small className="text-danger mb-1">
                                {errors.email}
                            </small>
                        )}
                    </div>

                    <div className="form-group">
                        <label className="control-label">{t("admin.sidebar.settings.myAddress")}</label>
                        <textarea
                            className="form-control"
                            value={address}
                            onChange={(e) => setAddress(e.target.value)}
                            placeholder={t("admin.sidebar.settings.myAddress")}
                        />
                        {errors.address && (
                            <small className="text-danger mb-1">
                                {errors.address}
                            </small>
                        )}
                    </div>
                    <div className="form-group">
                        <label className="control-label">{t("admin.sidebar.settings.color")}</label>
                        <input
                            type="color"
                            className="form-control"
                            value={color}
                            onChange={(e) => setColor(e.target.value)}
                        />
                    </div>
                    <div className="form-group">
                        <label className="control-label">{t("admin.sidebar.settings.myPhone")}</label>
                        <StyledPhoneInput
                            country={'il'}
                            value={phone}
                            onChange={(phone) => {
                                setPhone(phone);
                            }}
                            inputClass="form-control"
                            inputProps={{
                                name: 'phone',
                                required: true,
                                placeholder: t("admin.sidebar.settings.myPhone"),
                            }}
                        />
                    </div>
                    <div className="form-group">
                        <label className="control-label">{t("admin.sidebar.settings.language")}</label>
                        <select
                            className="form-control"
                            value={lng}
                            onChange={(e) => setLng(e.target.value)}
                        >
                            <option value="">{t("admin.sidebar.settings.selectLang")}</option>
                            <option value="heb">{t("worker.settings.Hebrew")}</option>
                            <option value="en">{t("worker.settings.English")}</option>
                        </select>
                    </div>
                    <div className="form-group">
                        <label
                            className="control-label"
                            style={{ display: "block" }}
                        >
                            {t("admin.sidebar.settings.verification")}
                        </label>
                        <input
                            type="file"
                            onChange={handleChange}
                            accept="image/*"
                            style={{
                                display: "block",
                                height: "unset",
                                border: "none",
                            }}
                        />
                        <img
                            src={file}
                            className="img-fluid mt-2"
                            style={{ maxWidth: "200px" }}
                        />
                    </div>
                    <div className="form-group">
                        <div className="toggle-switch">
                            <div className="switch">
                                <span className="mr-2">{t("admin.sidebar.settings.verification")}</span>
                                <input
                                    onChange={() => setTwostepverification(prev => !prev)}
                                    type="checkbox"
                                    id="switch"
                                    checked={twostepverification}
                                />
                                <label htmlFor="switch">
                                    <span className="slider round"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div className="form-group text-center">
                        <input
                            type="submit"
                            onClick={handleSubmit}
                            className="btn navyblue saveBtn"
                        />
                    </div>
                </form>
            </div>
            { loading && <FullPageLoader visible={loading}/>}
        </div>
    );
}
