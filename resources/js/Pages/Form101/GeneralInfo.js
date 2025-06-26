import React, { useEffect, useState } from "react";
import TextField from "./inputElements/TextField";
import SelectElement from "./inputElements/SelectElement";
import DateField from "./inputElements/DateField";
import RadioButtonGroup from "./inputElements/RadioButtonGroup";
import { useTranslation } from "react-i18next";
import { countryOption } from "./cityCountry";
import { handleHeicConvert } from "../../Utils/common.utils";


export default function GeneralInfo({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    setFieldValue,
    handleBubbleToggle,
    activeBubble,
    handleFileChange,
    form_submitted_at,
    form_created_at,
    dateOfBegin
}) {

    const [indentityType, setIndentityType] = useState(values.employeecountry);
    useEffect(() => {
        if (values.employeecountry === "Israel") {
            setIndentityType("IDNumber");
            setFieldValue("employeeIdentityType", "IDNumber");
        }
        // Only update identity type if it's not yet set or country changes to Israel
        if (values.employeeIdentityType === "" || values.employeecountry !== "Israel") {
            setIndentityType("Passport");
            setFieldValue("employeeIdentityType", "Passport");
        }
    }, [values.employeecountry]);

    const { t } = useTranslation();

    const sexOptions = [
        { label: t("form101.label_male"), value: "Male" },
        { label: t("form101.label_female"), value: "Female" },
    ];

    const maritalStatusOptions = [
        { label: t("form101.status_single"), value: "Single" },
        { label: t("form101.status_married"), value: "Married" },
        { label: t("form101.status_divorcee"), value: "Divorced" },
        { label: t("form101.status_widower"), value: "Widowed" },
        { label: t("form101.status_separated"), value: "Separated" },
    ];

    const isIsraeliResidentOptions = [
        { label: t("form101.label_yes"), value: "Yes" },
        { label: t("form101.label_no"), value: "No" },
    ];

    const isKibbutzMemberOptions = [
        { label: t("form101.label_yes"), value: "Yes" },
        { label: t("form101.label_no"), value: "No" },
    ];

    const isHealthFundMemberOptions = [
        { label: t("form101.label_yes"), value: "Yes" },
        { label: t("form101.label_no"), value: "No" },
    ];
    const HealthFundname = [
        { label: "Clalit", value: "Clalit" },
        { label: "Maccabi", value: "Maccabi" },
        { label: "Meuhedet", value: "Meuhedet" },
        { label: "Leumit", value: "Leumit" },
    ];

    const tips = {
        "First Name": t("form101.step1.validation.first_name"),
        "Last Name": t("form101.step1.validation.last_name"),
        "Identification By": t("form101.step1.validation.identification"),
        "Passport": t("form101.step1.validation.passport"),
        "ID Number": t("form101.step1.validation.id_number"),
        "Photocopy Of ID Card": t("form101.step1.validation.photocopy_id"),
        "Date Of Birth": t("form101.step1.validation.dob"),
        "Date Of Immigration": t("form101.step1.validation.date_of_immigration"),
        "City": t("form101.step1.validation.city"),
        "Street": t("form101.step1.validation.street"),
        "House Number": t("form101.step1.validation.house_number"),
        "Postal Code": t("form101.step1.validation.postal_code"),
        "Phone Number": t("form101.step1.validation.Phone_Number"),
        "Mobile Number": t("form101.step1.validation.Mobile_Number"),
        "Email": t("form101.step1.validation.Email"),
        "Start Date Of Job": t("form101.step1.validation.Start_Date_Of_Job"),
        "Role": t("form101.step1.validation.Role"),
        "Sex": t("form101.step1.validation.Sex"),
        "Israeli Resident": t("form101.step1.validation.Israeli_Resident"),
        "Member Of Kibbutz/Cooperative Session": t("form101.step1.validation.Member_Of_Kibbutz"),
        "Health Fund Number": t("form101.step1.validation.first_name"),
        "Marital Status": t("form101.step1.validation.Marital_Status")
    }

    return (
        <div className="">
            <p className="navyblueColor font-34 mt-4 font-w-500">{t("form101.step1.broom")}</p>
            <div className="row mt-3">
                <section className="col-xl">
                    <p className="font-w-500">
                        {t("form101.step1.heading")}
                    </p>
                    <div className="mt-3 lightgrey p-4" style={{ borderRadius: "3px" }}>
                        <p className="mb-3 font-w-500 font-20">{t("form101.step1.tips_title")}</p>
                        <div>
                            {Object.entries(tips).map(([key, value], index) => (
                                <p className="mb-1" key={index}>
                                    <span className="font-w-500">{key} - </span>
                                    {value}
                                </p>
                            ))}
                        </div>
                    </div>
                </section>
                <section className="col p-4">
                    <p className="mb-3 font-w-500 font-20">{t("form101.step1.info")}</p>
                    {/* GeneralInformation */}
                    <div className="box-heading">
                        <div className="">
                            <div className="row justify-content-between">
                                <div className="col-sm" style={{ position: 'relative' }}>
                                    <TextField
                                        name="employeeFirstName"
                                        label={t("form101.label_firstName")}
                                        value={values.employeeFirstName}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        readonly={true}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        error={
                                            touched.employeeFirstName && errors.employeeFirstName
                                                ? errors.employeeFirstName
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeFirstName' && (
                                        <div className="d-flex justify-content-end" style={{ position: 'relative' }}>
                                            <div className="speech up">
                                                {t("form101.step1.validation.first_name")}
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <div className="col-sm">
                                    <TextField
                                        name="employeeLastName"
                                        label={t("form101.label_lastName")}
                                        value={values.employeeLastName}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        readonly={true}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        error={
                                            touched.employeeLastName && errors.employeeLastName
                                                ? errors.employeeLastName
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeLastName' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                {t("form101.step1.validation.last_name")}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm" id="employeeIsraeliResident">
                                    <RadioButtonGroup
                                        name="employeeIsraeliResident"
                                        label={t("form101.israeli_resident")}
                                        options={isIsraeliResidentOptions}
                                        value={values.employeeIsraeliResident}
                                        onChange={handleChange}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        onBlur={handleBlur}
                                        isFlex={true}
                                        error={
                                            touched.employeeIsraeliResident &&
                                                errors.employeeIsraeliResident
                                                ? errors.employeeIsraeliResident
                                                : ""
                                        }
                                        disabled={true}
                                        required
                                    />
                                    {activeBubble === 'employeeIsraeliResident' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                {t("form101.step1.validation.Israeli_Resident")}
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <div className="col-sm">
                                    <RadioButtonGroup
                                        name="employeeSex"
                                        label={t("form101.label_sex")}
                                        options={sexOptions}
                                        value={values.employeeSex}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        isFlex={true}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        error={
                                            touched.employeeSex && errors.employeeSex
                                                ? errors.employeeSex
                                                : ""
                                        }
                                        disabled={true}
                                        required
                                    />
                                    {activeBubble === 'employeeSex' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                {t("form101.step1.validation.Sex")}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm">
                                    <label className="control-label d-block">
                                        {t("form101.idBy")} *
                                    </label>
                                    <div className="d-flex">
                                        <label className="radio">
                                            <input
                                                type="radio"
                                                name="employeeIdentityType"
                                                value="IDNumber"
                                                className="mr-2"
                                                checked={indentityType === "IDNumber" ? true : false}
                                                onChange={(e) => {
                                                    setIndentityType(e.target.value);
                                                    handleChange(e);
                                                    setFieldValue("employeeIdentityType", e.target.value);
                                                }}
                                                disabled={true}
                                            />
                                            <span className="">{t("form101.id_num")}</span>
                                        </label>
                                        <label className="radio mt-0">
                                            <input
                                                type="radio"
                                                name="employeeIdentityType"
                                                value="Passport"
                                                className="mr-2"
                                                checked={indentityType === "Passport" ? true : false}
                                                onChange={(e) => {
                                                    setIndentityType(e.target.value);
                                                    handleChange(e);
                                                    setFieldValue("employeeIdentityType", e.target.value);

                                                }}
                                                disabled={true}
                                            />
                                            <span className="">{t("form101.passport_foreign")}</span>
                                        </label>
                                    </div>
                                    {touched.employeeIdentityType &&
                                        errors.employeeIdentityType && (
                                            <p className="text-danger">
                                                {errors.employeeIdentityType}
                                            </p>
                                        )}
                                </div>
                                <div className="col-sm">
                                    <SelectElement
                                        name={"employeecountry"}
                                        label={t("form101.country_passport")}
                                        value={values.employeecountry}
                                        onChange={(e) => {
                                            const selectedCountry = e.target.value;
                                            handleChange(e);

                                            // Set identity type based on country, but only modify the identity field
                                            if (selectedCountry === "Israel") {
                                                setIndentityType("IDNumber");
                                                setFieldValue("employeeIdentityType", "IDNumber");
                                            } else {
                                                setIndentityType("Passport");
                                                setFieldValue("employeeIdentityType", "Passport");
                                            }

                                            setFieldValue("employeecountry", selectedCountry); // Update country
                                        }}
                                        onBlur={handleBlur}
                                        error={
                                            touched.employeecountry && errors.employeecountry
                                                ? errors.employeecountry
                                                : ""
                                        }
                                        options={countryOption}
                                        disabled={true}
                                    />
                                </div>
                            </div>
                            {indentityType === "Passport" ? (
                                <>

                                    <div className="row">
                                        <div className="col-sm">
                                            <TextField
                                                name="employeePassportNumber"
                                                label={t("form101.passport_num")}
                                                value={values.employeecountry !== "Israel" ? values.employeePassportNumber : ""}
                                                onChange={handleChange}
                                                onBlur={handleBlur}
                                                toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                                error={
                                                    touched.employeePassportNumber &&
                                                        errors.employeePassportNumber
                                                        ? errors.employeePassportNumber
                                                        : ""
                                                }
                                                required
                                            // readonly={values.employeePassportNumber === null ? false : true}
                                            />
                                            {activeBubble === 'employeePassportNumber' && (
                                                <div className="d-flex justify-content-end">
                                                    <div className="speech up">
                                                        {t("form101.step1.validation.passport")}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                        <div className="col-sm">
                                            <label htmlFor="employeepassportCopy">
                                                {t("form101.passport_photo")}
                                            </label>
                                            <br />
                                            <div className="input_container">
                                                <input
                                                    className="w-100"
                                                    type="file"
                                                    name="employeepassportCopy"
                                                    id="employeepassportCopy"
                                                    title={values.employeepassportCopy}
                                                    accept=".jpg,.jpeg,.png,.heic,.heif,image/*"  // explicitly include HEIC/HEIF
                                                    onChange={async (e) => {
                                                        e.persist(); // keeps the event alive
                                                        const originalFile = e.target.files[0];
                                                        const processedFile = await handleHeicConvert(originalFile);
                                                        if (originalFile) {
                                                            const fileSizeInMB = originalFile.size / (1024 * 1024); // Convert file size to MB
                                                            if (fileSizeInMB > 10) {
                                                                alert("File size must be less than 10MB"); // Show error message
                                                                return;
                                                            }
                                                            setFieldValue("employeepassportCopy", processedFile);
                                                            handleFileChange(e, "passport");
                                                        }
                                                    }}
                                                    onBlur={handleBlur}
                                                />
                                            </div>
                                            {touched.employeepassportCopy &&
                                                errors.employeepassportCopy && (
                                                    <p className="text-danger">
                                                        {errors.employeepassportCopy}
                                                    </p>
                                                )}
                                        </div>
                                    </div>
                                    <div className="row mt-3 mb-4 d-flex align-items-end">
                                        <div className="col-sm">
                                            <label htmlFor="employeeResidencePermit"
                                                style={{ marginBottom: "0", width: "100%" }}
                                            >
                                                {t("form101.PhotoCopyResident")}
                                            </label>
                                            <div className="input_container" style={{ height: "42px" }}>
                                                <input
                                                    type="file"
                                                    name="employeeResidencePermit"
                                                    id="employeeResidencePermit"
                                                    className="form-control man p-0 border-0"
                                                    style={{ fontSize: "unset", backgroundColor: "unset", }}
                                                    title={values.employeeResidencePermit}
                                                    accept=".jpg,.jpeg,.png,.heic,.heif,image/*"  // explicitly include HEIC/HEIF
                                                    onChange={async (e) => {
                                                        e.persist(); // keeps the event alive
                                                        const originalFile = e.target.files[0];
                                                        const processedFile = await handleHeicConvert(originalFile);

                                                        setFieldValue(
                                                            "employeeResidencePermit",
                                                            processedFile
                                                        )
                                                        handleFileChange(e, "visa");
                                                    }}
                                                    onBlur={handleBlur}
                                                />
                                            </div>
                                            {touched.employeeResidencePermit &&
                                                errors.employeeResidencePermit && (
                                                    <p className="text-danger">
                                                        {errors.employeeResidencePermit}
                                                    </p>
                                                )}
                                        </div>
                                    </div>
                                </>
                            ) : (
                                <>
                                    <div className="row">
                                        <div className="col-sm">
                                            <TextField
                                                name="employeeIdNumber"
                                                label={t("form101.id_num")}
                                                value={values.employeecountry === "Israel" ? values.employeeIdNumber : ""}
                                                className="form-control man p-0 border-0"
                                                style={{ fontSize: "unset", backgroundColor: "unset", }}
                                                onChange={handleChange}
                                                onBlur={handleBlur}
                                                toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                                error={
                                                    touched.employeeIdNumber &&
                                                        errors.employeeIdNumber
                                                        ? errors.employeeIdNumber
                                                        : ""
                                                }
                                                required
                                            // readonly={values.employeeIdNumber === null ? false : true}
                                            />
                                            {activeBubble === 'employeeIdNumber' && (
                                                <div className="d-flex justify-content-end">
                                                    <div className="speech up">
                                                        {t("form101.step1.validation.id_number")}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                        <div className="col-sm mb-2">
                                            <label htmlFor="employeeIdCardCopy">
                                                {t("form101.id_photocopy")}
                                            </label>
                                            <br />
                                            <div className="input_container">
                                                <input
                                                    className="w-100"
                                                    type="file"
                                                    name="employeeIdCardCopy"
                                                    id="employeeIdCardCopy"
                                                    title={values.employeeIdCardCopy}
                                                    accept=".jpg,.jpeg,.png,.heic,.heif,image/*"  // explicitly include HEIC/HEIF
                                                    onChange={async (e) => {
                                                        e.persist(); // keeps the event alive
                                                        const originalFile = e.target.files[0];
                                                        const processedFile = await handleHeicConvert(originalFile);
                                                        setFieldValue("employeeIdCardCopy", processedFile);
                                                        handleFileChange(e, "id_card");
                                                    }
                                                    }
                                                    onBlur={handleBlur}
                                                />
                                            </div>
                                            {/* {values &&
                                                values.employeeIdCardCopy && (
                                                    <p className="text-success">
                                                        {values.employeeIdCardCopy}
                                                    </p>
                                                )} */}
                                            {touched.employeeIdCardCopy &&
                                                errors.employeeIdCardCopy && (
                                                    <p className="text-danger">
                                                        {errors.employeeIdCardCopy}
                                                    </p>
                                                )}
                                        </div>
                                    </div>

                                </>
                            )}
                        </div>
                        <div className="">
                            <div className="row">
                                <div className="col-sm">
                                    <DateField
                                        name="employeeDob"
                                        label={t("form101.dob")}
                                        value={values.employeeDob}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        error={
                                            touched.employeeDob && errors.employeeDob
                                                ? errors.employeeDob
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeDob' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                Enter your date of birth in DD/MM/YYYY format.
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <div className={`w-100 ${indentityType === "Passport" ? "d-none" : 'd-block col-sm'}`}>
                                    {indentityType === "IDNumber" && (
                                        <>
                                            <DateField
                                                name="employeeDateOfAliyah"
                                                label={t("form101.dom")}
                                                value={values.employeeDateOfAliyah}
                                                onChange={handleChange}
                                                onBlur={handleBlur}
                                                toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                                error={
                                                    touched.employeeDateOfAliyah &&
                                                        errors.employeeDateOfAliyah
                                                        ? errors.employeeDateOfAliyah
                                                        : ""
                                                }
                                            />
                                            {activeBubble === 'employeeDateOfAliyah' && (
                                                <div className="d-flex justify-content-end">
                                                    <div className="speech up">
                                                        {t("form101.step1.validation.Start_Date_Of_Job")}
                                                    </div>
                                                </div>
                                            )}
                                        </>
                                    )}
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm">
                                    <TextField
                                        name="employeeCity"
                                        label={t("form101.City")}
                                        value={values.employeeCity}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        error={
                                            touched.employeeCity && errors.employeeCity
                                                ? errors.employeeCity
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeCity' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                {t("form101.step1.validation.city")}

                                            </div>
                                        </div>
                                    )}
                                </div>
                                <div className="col-sm">
                                    <TextField
                                        name="employeeStreet"
                                        label={t("form101.street")}
                                        value={values.employeeStreet}
                                        onChange={handleChange}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        onBlur={handleBlur}
                                        error={
                                            touched.employeeStreet && errors.employeeStreet
                                                ? errors.employeeStreet
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeStreet' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                {t("form101.step1.validation.street")}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm">
                                    <TextField
                                        name="employeeHouseNo"
                                        label={t("form101.ho_num")}
                                        value={values.employeeHouseNo}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        error={
                                            touched.employeeHouseNo && errors.employeeHouseNo
                                                ? errors.employeeHouseNo
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeHouseNo' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                {t("form101.step1.validation.house_number")}
                                            </div>
                                        </div>
                                    )}
                                </div>
                                {/* <div className="col-sm">
                                    <TextField
                                        name="employeePostalCode"
                                        label={t("form101.postal_code")}
                                        value={values.employeePostalCode}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        error={
                                            touched.employeePostalCode &&
                                                errors.employeePostalCode
                                                ? errors.employeePostalCode
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeePostalCode' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                {t("form101.step1.validation.postal_code")}
                                            </div>
                                        </div>
                                    )}
                                </div> */}
                            </div>
                            <div className="row">
                                <div className="col-sm">
                                    <TextField
                                        name="employeeMobileNo"
                                        label={t("form101.mob_num")}
                                        value={values.employeeMobileNo}
                                        onChange={handleChange}
                                        readonly={true}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        onBlur={handleBlur}
                                        error={
                                            touched.employeeMobileNo && errors.employeeMobileNo
                                                ? errors.employeeMobileNo
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeMobileNo' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                {t("form101.step1.validation.Mobile_Number")}
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <div className="col-sm">
                                    <TextField
                                        name="employeePhoneNo"
                                        label={t("form101.label_phNum")}
                                        value={values.employeePhoneNo}
                                        onChange={handleChange}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        onBlur={handleBlur}
                                        error={
                                            touched.employeePhoneNo && errors.employeePhoneNo
                                                ? errors.employeePhoneNo
                                                : ""
                                        }
                                    />
                                    {activeBubble === 'employeePhoneNo' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                {t("form101.step1.validation.Phone_Number")}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm">
                                    <TextField
                                        name="employeeEmail"
                                        label={t("form101.label_email")}
                                        value={values.employeeEmail}
                                        onChange={handleChange}
                                        readonly={true}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        onBlur={handleBlur}
                                        error={
                                            touched.employeeEmail && errors.employeeEmail
                                                ? errors.employeeEmail
                                                : ""
                                        }
                                        required={true}
                                    />
                                    {activeBubble === 'employeeEmail' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                {t("form101.step1.validation.Email")}
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <div className="col-sm">
                                    {
                                        !dateOfBegin && (
                                            <>
                                                <DateField
                                                    name="DateOfBeginningWork"
                                                    label="Start Date of Job"
                                                    value={values.DateOfBeginningWork}
                                                    onChange={(e) => {
                                                        if (e.target.value !== null) {
                                                            setFieldValue("DateOfBeginningWork", e.target.value);
                                                            handleChange(e);
                                                        }
                                                    }}
                                                    toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                                    onBlur={handleBlur}
                                                    error={
                                                        touched.DateOfBeginningWork && errors.DateOfBeginningWork
                                                            ? errors.DateOfBeginningWork
                                                            : ""
                                                    }
                                                    required
                                                />
                                                {activeBubble === 'DateOfBeginningWork' && (

                                                    <div className="d-flex justify-content-end">
                                                        <div className="speech up">
                                                            {t("form101.step1.validation.Start_Date_Of_Job")}
                                                        </div>
                                                    </div>
                                                )}
                                            </>
                                        )
                                    }
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm">
                                    <RadioButtonGroup
                                        name="employeeHealthFundMember"
                                        label={t("form101.healthFundMem")}
                                        options={isHealthFundMemberOptions}
                                        value={values.employeeHealthFundMember}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        isFlex={true}
                                        error={
                                            touched.employeeHealthFundMember &&
                                                errors.employeeHealthFundMember
                                                ? errors.employeeHealthFundMember
                                                : ""
                                        }
                                        required
                                    />
                                </div>
                                {values.employeeHealthFundMember === "Yes" && (
                                    <div className="col-sm">
                                        <RadioButtonGroup
                                            name="employeeHealthFundname"
                                            label={t("form101.HealthFundName")}
                                            options={HealthFundname}
                                            value={values.employeeHealthFundname}
                                            onChange={handleChange}
                                            onBlur={handleBlur}
                                            isFlex={true}
                                            error={
                                                touched.employeeHealthFundname &&
                                                    errors.employeeHealthFundname
                                                    ? errors.employeeHealthFundname
                                                    : ""
                                            }
                                            required
                                        />
                                    </div>
                                )}
                            </div>

                            <div className="row">
                                <div className="col-sm">
                                    <RadioButtonGroup
                                        name="employeeCollectiveMoshavMember"
                                        label={t("form101.cop_member")}
                                        options={isKibbutzMemberOptions}
                                        value={values.employeeCollectiveMoshavMember}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        isFlex={true}
                                        error={
                                            touched.employeeCollectiveMoshavMember &&
                                                errors.employeeCollectiveMoshavMember
                                                ? errors.employeeCollectiveMoshavMember
                                                : ""
                                        }
                                        required
                                    />
                                </div>
                                {values.employeeCollectiveMoshavMember === "Yes" && (
                                    <div className="col-sm">
                                        <RadioButtonGroup
                                            name="employeemyIncomeToKibbutz"
                                            label={t("form101.myIncomeTrandfer")}
                                            options={isKibbutzMemberOptions}
                                            value={values.employeemyIncomeToKibbutz}
                                            onChange={handleChange}
                                            onBlur={handleBlur}
                                            isFlex={true}
                                            error={
                                                touched.employeemyIncomeToKibbutz &&
                                                    errors.employeemyIncomeToKibbutz
                                                    ? errors.employeemyIncomeToKibbutz
                                                    : ""
                                            }
                                            required
                                        />
                                    </div>
                                )}
                            </div>

                            <div className="">
                                <RadioButtonGroup
                                    name="employeeMaritalStatus"
                                    label={t("form101.martial_status")}
                                    options={maritalStatusOptions}
                                    resClassName="status"
                                    value={values.employeeMaritalStatus}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    isFlex={true}
                                    error={
                                        touched.employeeMaritalStatus &&
                                            errors.employeeMaritalStatus
                                            ? errors.employeeMaritalStatus
                                            : ""
                                    }
                                    required
                                />
                            </div>
                        </div>
                    </div>

                </section>
            </div>
        </div >
    );
}
