import i18next from "i18next";
import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";

export default function Acc() {
    const [name, setName] = useState("");
    const [address, setAddress] = useState("");
    const [email, setEmail] = useState("");
    const [file, setFile] = useState("");
    const [color, setColor] = useState("");
    const [phone, setPhone] = useState("");
    const [avatar, setAvatar] = useState("");
    const [lng, setLng] = useState("");
    const [errors, setErrors] = useState([]);
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

    const handleSubmit = (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append("name", name);
        formData.append("email", email);
        formData.append("address", address);
        formData.append("color", color);
        formData.append("avatar", avatar);
        formData.append("phone", phone);
        formData.append("lng", lng == 0 ? "heb" : lng);
        i18next.changeLanguage(lng);
        axios
            .post(`/api/admin/my-account`, formData, { headers })
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                } else {
                    alert.success(
                        "Account details has been updated successfully"
                    );
                }
            });
    };
    const getSetting = () => {
        axios.get("/api/admin/my-account", { headers }).then((response) => {
            setName(response.data.account.name);
            setColor(response.data.account.color);
            setEmail(response.data.account.email);
            setPhone(response.data.account.phone);
            setLng(response.data.account.lng);
            setAddress(response.data.account.address);
            setFile(response.data.account.avatar);
        });
    };
    useEffect(() => {
        getSetting();
    }, []);

    return (
        <div className="card">
            <div className="card-body">
                <form>
                    <div className="form-group">
                        <label className="control-label">My name</label>
                        <input
                            type="text"
                            className="form-control"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            placeholder="My name"
                        />
                    </div>
                    <div className="form-group">
                        <label className="control-label">My email</label>
                        <input
                            type="text"
                            className="form-control"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder="My email"
                        />
                        {errors.email && (
                            <small className="text-danger mb-1">
                                {errors.email}
                            </small>
                        )}
                    </div>

                    <div className="form-group">
                        <label className="control-label">My address</label>
                        <textarea
                            className="form-control"
                            value={address}
                            onChange={(e) => setAddress(e.target.value)}
                            placeholder="My address"
                        />
                        {errors.address && (
                            <small className="text-danger mb-1">
                                {errors.address}
                            </small>
                        )}
                    </div>
                    <div className="form-group">
                        <label className="control-label">Color</label>
                        <input
                            type="color"
                            className="form-control"
                            value={color}
                            onChange={(e) => setColor(e.target.value)}
                        />
                    </div>
                    <div className="form-group">
                        <label className="control-label">My phone</label>
                        <input
                            type="text"
                            className="form-control"
                            value={phone}
                            onChange={(e) => setPhone(e.target.value)}
                            placeholder="My phone"
                        />
                    </div>
                    <div className="form-group">
                        <label className="control-label">Language</label>
                        <select
                            className="form-control"
                            value={lng}
                            onChange={(e) => setLng(e.target.value)}
                        >
                            <option value="">--- Select language ---</option>
                            <option value="heb">Hebrew</option>
                            <option value="en">English</option>
                        </select>
                    </div>
                    <div className="form-group">
                        <label
                            className="control-label"
                            style={{ display: "block" }}
                        >
                            Upload profile image
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
                    <div className="form-group text-center">
                        <input
                            type="submit"
                            onClick={handleSubmit}
                            className="btn btn-danger saveBtn"
                        />
                    </div>
                </form>
            </div>
        </div>
    );
}
