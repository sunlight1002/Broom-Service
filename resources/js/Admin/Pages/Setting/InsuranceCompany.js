import axios from "axios";
import React, { useState, useEffect } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function InsuranceCompany() {
  const { t, i18n } = useTranslation();
  const [formValues, setFormValues] = useState({
    name: "",
    email: "",
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

  const getCompany = () => {
    axios.get(`/api/admin/insurance-companies`, { headers }).then((res) => {
      setFormValues({
        name: res.data?.insurance_companies?.name,
        email: res.data?.insurance_companies?.email,
      });
    });
  };
  
  useEffect(() => {
    getCompany();
  }, [])
  

  const handleSubmit = (e) => {
    e.preventDefault();
    setLoading(true);

    const data = {
      name: formValues.name,
      email: formValues.email
    };

    axios
      .post(`/api/admin/insurance-companies`, data, { headers })
      .then((res) => {
        if (res.data.errors) {
          setLoading(false);
          setErrors(res.data.errors);
          for (let e in res.data.errors) {
            alert.error(res.data.errors[e]);
          }
        } else {
          setLoading(false);
          getCompany();
          alert.success("Insurance company updated successfully!");
        }
      })
      .catch((error) => {
        setLoading(false);
        alert.error("An unexpected error occurred.");
      });
  };

  return (
    <div id="container">
      <Sidebar />
      <div id="content">
        <h1 className="page-title">{t("global.update_insurance_company")}</h1>
        <form onSubmit={handleSubmit}>
          <div className="row">
            <div className="col-lg-6 col-12">
              <div className="dashBox p-0 p-md-4">
                <div className="form-group">
                  <label className="control-label">
                    {t("global.name")}
                  </label>
                  <input
                    type="text"
                    className="form-control"
                    value={formValues.name}
                    onChange={(e) => {
                      setFormValues({
                        ...formValues,
                        name: e.target.value,
                      })
                    }}
                    placeholder="Enter name"
                    required
                  />
                </div>
                <div className="form-group">
                  <label className="control-label">
                    {t("admin.global.Email")}
                  </label>
                  <input
                    type="email"
                    className="form-control"
                    value={formValues.email}
                    onChange={(e) => {
                      setFormValues({
                        ...formValues,
                        email: e.target.value,
                      })
                    }}
                    placeholder="Enter email"
                    required
                  />
                </div>
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
