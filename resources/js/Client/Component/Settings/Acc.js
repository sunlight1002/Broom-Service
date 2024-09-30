import React, { useEffect, useState, useRef } from "react";
import { useAlert } from "react-alert";
import Moment from "moment";
import { useTranslation } from "react-i18next";
import { FaPlusCircle } from "react-icons/fa";
import { LuSave } from "react-icons/lu";
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
    const [firstname, setFirstName] = useState("");
    const [lastname, setLastName] = useState("");
    const [invoicename, setInvoicename] = useState("");
    const [email, setEmail] = useState("");
    const [phone, setPhone] = useState("");
    const [city, setCity] = useState("");
    const [street, setStreet] = useState("");
    const [apt, setApt] = useState("");
    const [floor, setFloor] = useState("");
    const [entrence, setEntrence] = useState("");
    const [dob, setDob] = useState("");
    const [lng, setLng] = useState("");
    const [zipcode, setZipcode] = useState("");
    const [file, setFile] = useState("");
    const [avatar, setAvatar] = useState("");
    const [errors, setErrors] = useState([]);
    const [twostepverification, setTwostepverification] = useState(false);
    const { t } = useTranslation();
    const alert = useAlert();
    const fileInputRef = useRef(null);
    const imageRef = useRef(null);
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const handleChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                imageRef.current.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    };

    const getSetting = () => {
        axios.get("/api/client/my-account", { headers }).then((response) => {
            setFirstName(response.data.account.firstname);
            setLastName(response.data.account.lastname);
            setInvoicename(response.data.account.invoicename);
            setDob(response.data.account.dob);
            setCity(response.data.account.city);
            setStreet(response.data.account.street_n_no);
            setEntrence(response.data.account.entrence_code);
            setApt(response.data.account.apt_no);
            setFloor(response.data.account.floor);
            setLng(response.data.account.lng);
            setZipcode(response.data.account.zipcode);
            setEmail(response.data.account.email);
            setPhone(response.data.account.phone);
            setFile(response.data.account.avatar);
            setTwostepverification(response.data.account.two_factor_enabled === 1);
        });
    };

    const handleClick = () => {
        fileInputRef.current.click();
    };

    const handleSubmit = (e) => {
        location.reload();

        const formData = new FormData();
        formData.append("firstname", firstname);
        formData.append("lastname", lastname);
        formData.append("invoicename", invoicename);
        formData.append("city", city);
        formData.append("street_n_no", street);
        formData.append("floor", floor);
        formData.append("entrence_code", entrence);
        formData.append("apt_no", apt);
        formData.append("dob", dob);
        formData.append("lng", lng == 0 ? "heb" : lng);
        formData.append("zipcode", zipcode);
        formData.append("email", email);
        formData.append("avatar", avatar);
        formData.append("phone", phone);
        formData.append("twostepverification", twostepverification);
        // for (let [key, value] of formData.entries()) {
        //     console.log(`${key}: ${value}`);
        // }
        axios
            .post(`/api/client/my-account`, formData, { headers })
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                } else {
                    localStorage.setItem("client-lng", lng)
                    alert.success(t("client.settings.updateSuccess"));
                }
            });
    };


    useEffect(() => {
        getSetting();
    }, []);

    return (
        <div className="card" style={{
            background: "#FAFBFC",
            boxShadow: "none",
            border: "1px solid #E5EBF1",
        }}>
            <p className="ml-3 mt-4"
                style={{ fontWeight: "bolder" }}
            >{t("client.settings.my_account")}</p>
            <div className="card-body">
                <form>
                    <div className="form-group">
                        {/* <label className="control-label">{t("client.settings.avatar_txt")}</label> */}
                        <div className="position-relative" style={{height: "6.5rem", width: "6.5rem"}}>
                            <div className="image-uploader" onClick={handleClick}>
                                <div className="avatar-upload">
                                    <input
                                        type="file"
                                        ref={fileInputRef}
                                        onChange={handleChange}
                                        style={{ display: "none" }}
                                    />
                                    <div className="image-preview">
                                        {file && <img src={file} ref={imageRef} alt="avatar" className="avatar" />}
                                    </div>
                                </div>
                            </div>
                                    <div className="placeholder pe-auto" ref={imageRef} onClick={handleClick}>
                                        <FaPlusCircle />
                                    </div>
                        </div>
                    </div>
                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "50%" }}>
                            {t("client.settings.f_nm")}
                        </label>
                        <input
                            type="text"
                            className="form-control"
                            value={firstname}
                            onChange={(e) => setFirstName(e.target.value)}
                            placeholder={t("client.settings.f_nm")}
                        />
                        {errors.firstname && (
                            <small className="text-danger mb-1">
                                {errors.firstname}
                            </small>
                        )}
                    </div>

                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "50%" }}>
                            {t("client.settings.l_nm")}
                        </label>
                        <input
                            type="text"
                            className="form-control"
                            value={lastname}
                            onChange={(e) => setLastName(e.target.value)}
                            placeholder={t("client.settings.l_nm")}
                        />
                        {errors.lastname && (
                            <small className="text-danger mb-1">
                                {errors.lastname}
                            </small>
                        )}
                    </div>
                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "50%" }}>
                            {t("client.settings.inm")}
                        </label>
                        <input
                            type="text"
                            className="form-control"
                            value={invoicename}
                            onChange={(e) => setInvoicename(e.target.value)}
                            placeholder={t("client.settings.inm")}
                        />
                        {errors.invoicename && (
                            <small className="text-danger mb-1">
                                {errors.invoicename}
                            </small>
                        )}
                    </div>
                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "50%" }}>
                            {t("client.settings.email")}
                        </label>
                        <input
                            type="text"
                            className="form-control"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder={t("client.settings.email")}
                        />
                        {errors.email && (
                            <small className="text-danger mb-1">
                                {errors.email}
                            </small>
                        )}
                    </div>
                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "50%" }}>
                            {t("client.settings.dob")}
                        </label>
                        <input
                            type="date"
                            id="dob"
                            value={Moment(dob).format("Y-MM-DD")}
                            className="form-control"
                            onChange={(e) => setDob(e.target.value)}
                        />
                        {errors.dob && (
                            <small className="text-danger mb-1">
                                {errors.dob}
                            </small>
                        )}
                    </div>

                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "50%" }}>
                            {t("client.settings.lng")}
                        </label>
                        <select
                            className="form-control"
                            value={lng}
                            onChange={(e) => setLng(e.target.value)}
                        >
                            <option value="">
                                {t("client.settings.select")}
                            </option>
                            <option value="heb">
                                {t("client.settings.Hebrew")}
                            </option>
                            <option value="en">
                                {t("client.settings.English")}
                            </option>
                        </select>
                    </div>

                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "50%" }}>
                            {t("client.settings.city")}
                        </label>
                        <input
                            type="text"
                            className="form-control"
                            value={city}
                            onChange={(e) => setCity(e.target.value)}
                            placeholder={t("client.settings.city")}
                        />
                        {errors.city && (
                            <small className="text-danger mb-1">
                                {errors.city}
                            </small>
                        )}
                    </div>
                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "50%" }}>
                            {t("client.settings.street_label")}
                        </label>
                        <input
                            type="text"
                            className="form-control"
                            value={street}
                            onChange={(e) => setStreet(e.target.value)}
                            placeholder={t("client.settings.street_label")}
                        />
                        {errors.street_n_no && (
                            <small className="text-danger mb-1">
                                {errors.street_n_no}
                            </small>
                        )}
                    </div>
                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "50%" }}>
                            {t("client.settings.e_code")}
                        </label>
                        <input
                            type="number"
                            className="form-control"
                            value={entrence}
                            onChange={(e) => setEntrence(e.target.value)}
                            placeholder={t("client.settings.e_code")}
                        />
                        {errors.entrence_code && (
                            <small className="text-danger mb-1">
                                {errors.entrence_code}
                            </small>
                        )}
                    </div>
                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "50%" }}>
                            {t("client.settings.apt_no")}
                        </label>
                        <input
                            type="number"
                            className="form-control"
                            value={apt}
                            onChange={(e) => setApt(e.target.value)}
                            placeholder={t("client.settings.apt_no")}
                        />
                        {errors.apt_no && (
                            <small className="text-danger mb-1">
                                {errors.apt_no}
                            </small>
                        )}
                    </div>
                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "50%" }}>
                            {t("client.settings.floor_no")}
                        </label>
                        <input
                            type="number"
                            className="form-control"
                            value={floor}
                            onChange={(e) => setFloor(e.target.value)}
                            placeholder={t("client.settings.floor_no")}
                        />
                        {errors.floor && (
                            <small className="text-danger mb-1">
                                {errors.floor}
                            </small>
                        )}
                    </div>
                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "50%" }}>
                            {t("client.settings.zip")}
                        </label>
                        <input
                            type="text"
                            className="form-control"
                            value={zipcode}
                            onChange={(e) => setZipcode(e.target.value)}
                            placeholder={t("client.settings.zip")}
                        />
                        {errors.zipcode && (
                            <small className="text-danger mb-1">
                                {errors.zipcode}
                            </small>
                        )}
                    </div>

                    <div className="form-group d-flex align-items-center">
                        <label className="control-label" style={{ width: "50%" }}>
                            {t("client.settings.phone")}
                        </label>
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
                                placeholder: t("client.settings.phone"),
                            }}
                        />
                        {errors.phone && (
                            <small className="text-danger mb-1">
                                {errors.phone}
                            </small>
                        )}
                    </div>

                    <div className="form-group d-flex align-items-center">
                    <div className="toggle-switch">
                            <div className="switch">
                                <span className="mr-2">Two step Verification</span>
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

                    <div className="form-group text-right">
                        <button
                            type="submit"
                            onClick={handleSubmit}
                            className="btn navyblue saveBtn"
                        ><span className="d-flex align-items-center"><LuSave className="mr-1" />{t("client.settings.save")}</span></button>
                    </div>
                </form>
            </div>
        </div>
    );
}
