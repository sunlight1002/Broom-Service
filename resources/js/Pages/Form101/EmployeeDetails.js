import React, { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";

export default function EmployeeDetails({ values }) {
    const [indentityType, setIndentityType] = useState("");
    const { t } = useTranslation();

    useEffect(() => {
        if (values.employeecountry === "Israel") {
            setIndentityType("IDNumber");
        } else {
            setIndentityType("Passport");
        }
    }, [values.employeecountry, values.employeeIdentityType]);


    return (
        <div className="">
            <p className="navyblueColor font-24 font-w-500 mt-3 mb-2">{t("form101.employee_details")}</p>
            <div className="row">

                <div className="col">
                    <div className="text-start form-group">
                        <label htmlFor="employeeFirstName" className="control-label font-w-500 navyblueColor">
                            {t("form101.label_firstName")} {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeeFirstName"
                                id="employeeFirstName"
                                defaultValue={values.employeeFirstName}
                                readOnly={true}
                            />
                        </div>
                    </div>
                    <div className="text-start form-group">
                        <label htmlFor="employeeIdentityType" className="control-label font-w-500 navyblueColor">
                            {t("form101.idBy")} {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeeIdentityType"
                                id="employeeIdentityType"
                                defaultValue={indentityType}
                                readOnly={true}
                            />
                        </div>
                    </div>

                    <div className="form-group">
                        <label htmlFor="employeeDob" className="control-label">
                            {t("form101.dob")} {"*"}
                        </label>
                        <br />
                        <input
                            type="date"
                            className="form-control"
                            name="employeeDob"
                            id="employeeDob"
                            defaultValue={values.employeeDob}
                            readOnly={true}
                        />
                    </div>
                    <div className="text-start form-group">
                        <label htmlFor="employeeCity" className="control-label font-w-500 navyblueColor">
                            {t("form101.City")} {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeeCity"
                                id="employeeCity"
                                defaultValue={values.employeeCity}
                                readOnly={true}
                            />
                        </div>
                    </div>

                    <div className="text-start form-group">
                        <label htmlFor="employeeHouseNo" className="control-label font-w-500 navyblueColor">
                            {t("form101.ho_num")} {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeeHouseNo"
                                id="employeeHouseNo"
                                defaultValue={values.employeeHouseNo}
                                readOnly={true}
                            />
                        </div>
                    </div>

                    <div className="text-start form-group">
                        <label htmlFor="employeePhoneNo" className="control-label font-w-500 navyblueColor">
                            {t("form101.label_phNum")} {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeePhoneNo"
                                id="employeePhoneNo"
                                defaultValue={values.employeePhoneNo}
                                readOnly={true}
                            />
                        </div>
                    </div>

                    <div className="text-start form-group">
                        <label htmlFor="employeeEmail" className="control-label font-w-500 navyblueColor">
                            {t("form101.label_email")} {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeeEmail"
                                id="employeeEmail"
                                defaultValue={values.employeeEmail}
                                readOnly={true}
                            />
                        </div>
                    </div>

                    <div className="text-start form-group">
                        <label htmlFor="employeeMaritalStatus" className="control-label font-w-500 navyblueColor">
                            {t("form101.martial_status")} {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeeMaritalStatus"
                                id="employeeMaritalStatus"
                                defaultValue={values.employeeMaritalStatus}
                                readOnly={true}
                            />
                        </div>
                    </div>

                    <div className="text-start form-group">
                        <label htmlFor="employeeHealthFundMember" className="control-label font-w-500 navyblueColor">
                            {t("form101.healthFundMem")} {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeeHealthFundMember"
                                id="employeeHealthFundMember"
                                defaultValue={values.employeeHealthFundMember}
                                readOnly={true}
                            />
                        </div>
                    </div>

                    <div className="text-start form-group">
                        <label className="control-label">
                            {t("form101.country_passport")}
                            {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeecountry"
                                id="employeecountry"
                                defaultValue={values.employeecountry}
                                readOnly={true}
                            />
                        </div>
                    </div>

                </div>
                <div className="col">
                    <div className="text-start form-group">
                        <label htmlFor="employeeLastName" className="control-label font-w-500 navyblueColor">
                            {t("form101.label_lastName")} {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeeLastName"
                                id="employeeLastName"
                                defaultValue={values.employeeLastName}
                                readOnly={true}
                            />
                        </div>
                    </div>


                    {indentityType === "Passport" ? (
                        <div className="text-start form-group">
                            <label htmlFor="employeePassportNumber" className="control-label font-w-500 navyblueColor">
                                {t("form101.passport_num")} {"*"}
                            </label>
                            <br />
                            <div className="d-flex align-items-center">
                                <input
                                    className={`form-control man`}
                                    type="text"
                                    name="employeePassportNumber"
                                    id="employeePassportNumber"
                                    value={values.employeePassportNumber || ""}
                                    readOnly={true}
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="text-start form-group">
                            <label htmlFor="employeeIdNumber" className="control-label font-w-500 navyblueColor">
                                {t("form101.id_num")} {"*"}
                            </label>
                            <br />
                            <div className="d-flex align-items-center">
                                <input
                                    className={`form-control man`}
                                    type="text"
                                    name="employeeIdNumber"
                                    id="employeeIdNumber"
                                    value={values.employeeIdNumber || ""}
                                    readOnly={true}
                                />
                            </div>
                        </div>
                    )}

                    <div className="form-group">
                        {indentityType == "IDNumber" && (
                            <>
                                <label htmlFor="employeeDateOfAliyah" className="control-label">
                                    {t("form101.dom")} {"*"}
                                </label>
                                <br />
                                <input
                                    type="date"
                                    className="form-control"
                                    name="employeeDateOfAliyah"
                                    id="employeeDateOfAliyah"
                                    defaultValue={values.employeeDateOfAliyah}
                                    readOnly={true}
                                />
                            </>
                        )}
                        {indentityType == "Passport" && (
                            <>
                                <label htmlFor="Start Date Of Job" className="control-label">
                                    {t("form101.dom")} {"*"}
                                </label>
                                <br />
                                <input
                                    type="date"
                                    className="form-control"
                                    name="DateOfBeginningWork"
                                    id="DateOfBeginningWork"
                                    defaultValue={values.DateOfBeginningWork}
                                    readOnly={true}
                                />
                            </>
                        )}
                    </div>

                    <div className="text-start form-group">
                        <label htmlFor="employeeStreet" className="control-label font-w-500 navyblueColor">
                            {t("form101.street")} {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeeStreet"
                                id="employeeStreet"
                                defaultValue={values.employeeStreet}
                                readOnly={true}
                            />
                        </div>
                    </div>

                    <div className="text-start form-group">
                        <label htmlFor="employeePostalCode" className="control-label font-w-500 navyblueColor">
                            {t("form101.postal_code")} {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeePostalCode"
                                id="employeePostalCode"
                                defaultValue={values.employeePostalCode}
                                readOnly={true}
                            />
                        </div>
                    </div>
                    <div className="text-start form-group">
                        <label htmlFor="employeeMobileNo" className="control-label font-w-500 navyblueColor">
                            {t("form101.mob_num")} {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeeMobileNo"
                                id="employeeMobileNo"
                                defaultValue={values.employeeMobileNo}
                                readOnly={true}
                            />
                        </div>
                    </div>

                    <div className="text-start form-group">
                        <label htmlFor="employeeSex" className="control-label font-w-500 navyblueColor">
                            {t("form101.label_sex")} {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeeSex"
                                id="employeeSex"
                                defaultValue={values.employeeSex}
                                readOnly={true}
                            />
                        </div>
                    </div>

                    <div className="text-start form-group">
                        <label htmlFor="employeeIsraeliResident" className="control-label font-w-500 navyblueColor">
                            {t("form101.israeli_resident")} {"*"}
                        </label>
                        <br />
                        <div className="d-flex align-items-center">
                            <input
                                className={`form-control man`}
                                type="text"
                                name="employeeIsraeliResident"
                                id="employeeIsraeliResident"
                                defaultValue={values.employeeIsraeliResident}
                                readOnly={true}
                            />
                        </div>
                    </div>

                    {values.employeeHealthFundMember === "Yes" && (
                        <div className="text-start form-group">
                            <label htmlFor="employeeHealthFundMember" className="control-label font-w-500 navyblueColor">
                                {t("form101.HealthFundName")} {"*"}
                            </label>
                            <br />
                            <div className="d-flex align-items-center">
                                <input
                                    className={`form-control man`}
                                    type="text"
                                    name="employeeHealthFundMember"
                                    id="employeeHealthFundMember"
                                    defaultValue={values.employeeHealthFundMember}
                                    readOnly={true}
                                />
                            </div>
                        </div>
                    )}
                </div>

            </div>
        </div>
    );
}
